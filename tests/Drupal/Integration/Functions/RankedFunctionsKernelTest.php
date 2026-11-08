<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Functions;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class RankedFunctionsKernelTest extends KernelTestBase
{
	protected static $modules = [
		'system',
		'user',
		'node',
		'taxonomy',
		'file',
		'field',
		'text',
		'filter',
	];

	protected function setUp(): void
	{
		parent::setUp();
		$this->installEntitySchema('user');
		$this->installEntitySchema('node');
		$this->installEntitySchema('taxonomy_term');
		$this->installEntitySchema('file');
		$this->installSchema('file', ['file_usage']);
		$this->installSchema('node', ['node_access']);
		$this->installConfig(['system', 'user', 'node', 'filter']);
		User::create(['uid' => 0, 'name' => ''])->save();
		User::create(['uid' => 1, 'name' => 'admin'])->save();
		NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
	}

	private function requestWithSession(string $uri): Request
	{
		$request = Request::create($uri);
		$request->setSession(new Session(new MockArraySessionStorage()));
		return $request;
	}

	private function makeUser(string $name, array $permissions = []): User
	{
		$role = Role::create(['id' => 'r_' . $name, 'label' => ucfirst($name)]);
		foreach ($permissions as $permission) {
			$role->grantPermission($permission);
		}
		$role->save();
		$user = User::create(['name' => $name, 'status' => 1, 'roles' => [$role->id()]]);
		$user->save();
		return $user;
	}

	public function testFormatPlural(): void
	{
		self::assertSame('1 item', format_plural(1, '1 item', '@count items'));
		self::assertSame('0 items', format_plural(0, '1 item', '@count items'));
		self::assertSame('1234 items', format_plural(1234, '1 item', '@count items'));
		self::assertSame(
			'2 items for &lt;b&gt;',
			format_plural(2, '1 item', '@count items for @x', ['@x' => '<b>']),
		);
	}

	public function testFormatSize(): void
	{
		self::assertSame('1 byte', format_size(1));
		self::assertSame('0 bytes', format_size(0));
		self::assertSame('1023 bytes', format_size(1023));
		self::assertSame('1 KB', format_size(1024));
		self::assertSame('1.5 KB', format_size(1536));
		self::assertSame('1 MB', format_size(1048576));
		self::assertSame('1.18 MB', format_size(1234567));
		self::assertSame('1 GB', format_size(1073741824));
		self::assertSame('1 YB', format_size(1024 ** 8));
	}

	public function testFormatInterval(): void
	{
		self::assertSame('0 sec', format_interval(0));
		self::assertSame('1 sec', format_interval(1));
		self::assertSame('1 min 1 sec', format_interval(61));
		self::assertSame('1 hour 1 min', format_interval(3661));
		self::assertSame('1 hour', format_interval(3661, 1));
		self::assertSame('1 hour 1 min 1 sec', format_interval(3661, 3));
		self::assertSame('2 days 3 hours', format_interval(2 * 86400 + 3 * 3600 + 5));
		self::assertSame('1 year 1 month', format_interval(31536000 + 2592000 + 5));
		self::assertSame('2 weeks', format_interval(1209600, 1));
	}

	public function testFormatDate(): void
	{
		self::assertSame('2021-01-01 00:00', format_date(1609459200, 'custom', 'Y-m-d H:i', 'UTC'));
		self::assertSame(
			'2021-01-01 01:00',
			format_date(1609459200, 'custom', 'Y-m-d H:i', 'Europe/Paris'),
		);
		self::assertNotSame('', format_date(1609459200, 'short', '', 'UTC'));
		self::assertNotSame('', format_date(1609459200, 'no_such_type', '', 'UTC'));
		self::assertSame('2021', format_date('1609459200', 'custom', 'Y', 'UTC'));
	}

	public function testArgAndDestination(): void
	{
		$this->container
			->get('request_stack')
			->push($this->requestWithSession('/node/5/edit?page=2&q=x&f[a]=1'));
		$this->container->get('path.current')->setPath('/node/5/edit');

		self::assertSame(['node', '5', 'edit'], arg());
		self::assertSame('node', arg(0));
		self::assertSame('edit', arg(2));
		self::assertNull(arg(3));
		self::assertSame('b', arg(1, 'a/b/c'));
		self::assertSame(['a', 'b'], arg(null, 'a/b'));

		self::assertSame(
			['destination' => 'node/5/edit?page=2&f%5Ba%5D=1'],
			drupal_get_destination(),
		);
	}

	public function testDestinationQueryParameterWins(): void
	{
		$this->container
			->get('request_stack')
			->push($this->requestWithSession('/node/5/edit?destination=admin/content'));
		$this->container->get('path.current')->setPath('/node/5/edit');
		self::assertSame(['destination' => 'admin/content'], drupal_get_destination());
	}

	public function testUserLoad(): void
	{
		$user = $this->makeUser('alice');
		$loaded = user_load($user->id());
		self::assertInstanceOf(User::class, $loaded);
		self::assertSame('alice', $loaded->getAccountName());
		self::assertFalse(user_load(9999));
	}

	public function testUserRoles(): void
	{
		$this->makeUser('alice', ['access content']);
		$this->makeUser('bob');
		$roles = user_roles();
		self::assertArrayHasKey('anonymous', $roles);
		self::assertArrayHasKey('authenticated', $roles);
		self::assertSame('Alice', $roles['r_alice']);
		self::assertArrayNotHasKey('anonymous', user_roles(true));
		self::assertArrayHasKey('authenticated', user_roles(true));
		self::assertSame(['r_alice'], array_keys(user_roles(true, 'access content')));
		self::assertSame([], user_roles(false, 'no such permission'));
	}

	public function testNodeAccess(): void
	{
		$viewer = $this->makeUser('viewer', ['access content']);
		$stranger = $this->makeUser('stranger');
		$creator = $this->makeUser('creator', ['access content', 'create page content']);
		$node = Node::create([
			'type' => 'page',
			'title' => 'Hello',
			'status' => 1,
			'uid' => $viewer->id(),
		]);
		$node->save();

		self::assertTrue(node_access('view', $node, $viewer));
		self::assertFalse(node_access('update', $node, $viewer));
		self::assertFalse(node_access('view', $node, $stranger));
		self::assertTrue(node_access('create', 'page', $creator));
		self::assertFalse(node_access('create', 'page', $viewer));
		self::assertFalse(node_access('create', 'no_such_type', $creator));
		self::assertFalse(node_access('view', $node, (object) ['uid' => 9999]));
		self::assertTrue(node_access('view', $node, (object) ['uid' => $viewer->id()]));
		self::assertFalse(node_access('view', null, $viewer));
		self::assertFalse(node_access('view', 'page', $viewer));
		self::assertFalse(node_access('view', (object) ['nid' => 1], $viewer));
		self::assertFalse(node_access('nonsense', $node, $viewer));
		self::assertFalse(node_access('view', $node, 'not an account'));

		$this->container->get('current_user')->setAccount($viewer);
		self::assertTrue(node_access('view', $node));
	}

	public function testEntityLabel(): void
	{
		$node = Node::create(['type' => 'page', 'title' => 'Hello']);
		self::assertSame('Hello', entity_label('node', $node));
		self::assertFalse(entity_label('node', null));
		self::assertFalse(entity_label('node', new \stdClass()));
	}

	public function testTaxonomyTermLoad(): void
	{
		Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
		$term = Term::create(['vid' => 'tags', 'name' => 'Green']);
		$term->save();

		$loaded = taxonomy_term_load($term->id());
		self::assertInstanceOf(Term::class, $loaded);
		self::assertSame('Green', $loaded->label());
		self::assertFalse(taxonomy_term_load('abc'));
		self::assertFalse(taxonomy_term_load(9999));
	}

	public function testFileLoad(): void
	{
		$file = File::create(['uri' => 'public://a.txt', 'filename' => 'a.txt']);
		$file->save();

		$loaded = file_load($file->id());
		self::assertInstanceOf(File::class, $loaded);
		self::assertSame('a.txt', $loaded->getFilename());
		self::assertFalse(file_load(9999));
	}

	public function testFileCreateUrl(): void
	{
		$this->container
			->get('request_stack')
			->push($this->requestWithSession('http://example.com/'));
		self::assertSame('/foo.jpg', file_create_url('/foo.jpg'));
		self::assertSame('https://cdn.test/a.png', file_create_url('https://cdn.test/a.png'));
		self::assertSame('http://example.com/sites/a%20b.txt', file_create_url('sites/a b.txt'));
		self::assertFalse(file_create_url('nosuchscheme://x.txt'));
		self::assertStringEndsWith('/a.txt', (string) file_create_url('public://a.txt'));
	}

	public function testTokens(): void
	{
		$token = drupal_get_token('foo');
		self::assertSame($token, drupal_get_token('foo'));
		self::assertNotSame($token, drupal_get_token('bar'));
		self::assertTrue(drupal_valid_token($token, 'foo'));
		self::assertFalse(drupal_valid_token($token, 'bar'));
		self::assertFalse(drupal_valid_token('bogus', 'foo'));
		self::assertFalse(drupal_valid_token($token));
		self::assertTrue(drupal_valid_token('bogus', 'foo', true));

		$this->container->get('current_user')->setAccount($this->makeUser('carol'));
		self::assertFalse(drupal_valid_token('bogus', 'foo', true));
	}
}
