<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D9;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\File\FileSystemInterface;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Symfony\Component\HttpFoundation\Request;

final class FilesTest extends LegacyTestCase
{
	protected static $modules = ['system', 'user', 'file'];

	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(MissingDependencyException::class);
		$this->container->get('module_installer')->install(['legacy_d9_files']);
	}

	public function testEnabledWithTheSwitchAndTheDeclaredCoreIsKept(): void
	{
		$this->enableLegacy('legacy_d9_files');
		self::assertTrue(\Drupal::moduleHandler()->moduleExists('legacy_d9_files'));
		$info = \Drupal::service('extension.list.module')->getExtensionInfo('legacy_d9_files');
		self::assertSame('^9', $info['anachron_core_requirement']);
	}

	public function testBlockPluginIsDiscoveredFromItsAnnotation(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$definition = \Drupal::service('plugin.manager.block')->getDefinition(
			'legacy_d9_files_latest',
		);
		self::assertSame('Saved files', (string) $definition['admin_label']);
	}

	private function prepareUploadDirectory(): void
	{
		$this->installEntitySchema('file');
		$directory = 'public://legacy-d9';
		\Drupal::service('file_system')->prepareDirectory(
			$directory,
			FileSystemInterface::CREATE_DIRECTORY,
		);
	}

	public function testFileSaveDataWritesTheFileAndReturnsItsUri(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$this->prepareUploadDirectory();
		$response = $this->doRequest(Request::create('/legacy-d9/files/save/note.txt'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('public://legacy-d9/note.txt', $this->getRawContent());
		self::assertSame('hello from d9', file_get_contents('public://legacy-d9/note.txt'));
		$logged = \Drupal::database()
			->select('legacy_d9_files_log', 'l')
			->fields('l', ['uri'])
			->execute()
			->fetchCol();
		self::assertSame(['public://legacy-d9/note.txt'], $logged);
	}

	public function testFileSaveDataReplacesAndRenamesByTheEraFlags(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$this->prepareUploadDirectory();
		$first = file_save_data(
			'one',
			'public://legacy-d9/flags.txt',
			FileSystemInterface::EXISTS_REPLACE,
		);
		$replaced = file_save_data(
			'two',
			'public://legacy-d9/flags.txt',
			FileSystemInterface::EXISTS_REPLACE,
		);
		$renamed = file_save_data('three', 'public://legacy-d9/flags.txt');
		self::assertSame($first->getFileUri(), $replaced->getFileUri());
		self::assertSame('public://legacy-d9/flags_0.txt', $renamed->getFileUri());
		self::assertFalse(
			file_save_data(
				'four',
				'public://legacy-d9/flags.txt',
				FileSystemInterface::EXISTS_ERROR,
			),
		);
		self::assertFalse(file_save_data('x', 'nowrapper://legacy-d9/flags.txt'));
	}

	public function testFileMungeFilenameRenamesAnInnerExtensionThatIsNotAllowed(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$this->doRequest(Request::create('/legacy-d9/files/munge/archive.php.txt'));
		self::assertSame('archive.php_.txt', $this->getRawContent());
		$this->doRequest(Request::create('/legacy-d9/files/munge/archive.jpg.txt'));
		self::assertSame('archive.jpg.txt', $this->getRawContent());
		self::assertSame('a.zip_.txt', file_munge_filename('a.zip.txt', 'txt jpg', false));
	}

	public function testFileMungeFilenameLeavesNamesAloneWhenInsecureUploadsAreAllowed(): void
	{
		$this->enableLegacy('legacy_d9_files');
		\Drupal::configFactory()
			->getEditable('system.file')
			->set('allow_insecure_uploads', true)
			->save();
		self::assertSame('a.php.txt', file_munge_filename('a.php.txt', 'txt', false));
	}

	public function testDrupalGetModuleSchemaReturnsTheTableDescription(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$this->doRequest(Request::create('/legacy-d9/files/schema'));
		self::assertSame('Files saved by the legacy D9 controller.', $this->getRawContent());
		self::assertSame([], drupal_get_module_schema('legacy_d9_files', 'missing_table'));
		self::assertSame(
			['legacy_d9_files_log'],
			array_keys(drupal_get_module_schema('legacy_d9_files')),
		);
	}

	public function testDrupalGetInstalledSchemaVersionReadsTheHighestUpdate(): void
	{
		$this->enableLegacy('legacy_d9_files');
		$this->doRequest(Request::create('/legacy-d9/files/version'));
		self::assertSame('9001', $this->getRawContent());
		self::assertArrayHasKey(
			'legacy_d9_files',
			drupal_get_installed_schema_version('legacy_d9_files', false, true),
		);
	}
}
