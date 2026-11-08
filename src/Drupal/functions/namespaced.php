<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal;

function module_load_include($type, $module, $name = null)
{
	\Drupal::moduleHandler()->loadInclude($module, $type, $name);
}
