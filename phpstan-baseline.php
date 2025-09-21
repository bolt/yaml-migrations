<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:merge\\(\\) has parameter \\$arr1 with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:merge\\(\\) has parameter \\$arr2 with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:merge\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:mergeDeepArray\\(\\) has parameter \\$arrays with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:mergeDeepArray\\(\\) has parameter \\$preserveIntegerKeys with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:mergeDeepArray\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:removeArrayRecursively\\(\\) has parameter \\$origin with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\ArrayMerge\\:\\:removeArrayRecursively\\(\\) has parameter \\$removables with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/ArrayMerge.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method mkdir\\(\\) on an unknown class Symfony\\\\Component\\\\Filesystem\\\\Filesystem\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method remove\\(\\) on an unknown class Symfony\\\\Component\\\\Filesystem\\\\Filesystem\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Filesystem\\\\Filesystem not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigration\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigration\\(\\) has parameter \\$migration with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigration\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationAdd\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationAdd\\(\\) has parameter \\$migration with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationAdd\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationDelete\\(\\) has parameter \\$migration with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationDelete\\(\\) never returns null so it can be removed from the return type\\.$#',
	'identifier' => 'return.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationDelete\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationRemove\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationRemove\\(\\) has parameter \\$migration with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:doMigrationRemove\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:list\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method YamlMigrate\\\\Migrate\\:\\:processIterator\\(\\) has parameter \\$list with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function trim expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$content of static method Webimpress\\\\SafeWriter\\\\FileWriter\\:\\:writeFile\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$version2 of static method Composer\\\\Semver\\\\Comparator\\:\\:greaterThan\\(\\) expects string, string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Property YamlMigrate\\\\Migrate\\:\\:\\$config type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];
$ignoreErrors[] = [
	'message' => '#^Property YamlMigrate\\\\Migrate\\:\\:\\$statistics type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Migrate.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
