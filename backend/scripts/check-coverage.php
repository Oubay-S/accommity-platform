<?php

declare(strict_types=1);

/**
 * Vérifie les seuils de couverture à partir du rapport Clover de PHPUnit.
 *
 * Usage :
 *   php scripts/check-coverage.php build/coverage/clover.xml 70 60
 *
 * Arguments :
 *   1. chemin du fichier clover.xml
 *   2. seuil minimal de statements couverts (défaut 70)
 *   3. seuil minimal de méthodes couvertes (défaut 60)
 */
$file = $argv[1] ?? 'build/coverage/clover.xml';
$minStatements = (float) ($argv[2] ?? 70);
$minMethods = (float) ($argv[3] ?? 60);

if (! is_file($file)) {
    fwrite(STDERR, "Rapport de couverture introuvable : {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Rapport de couverture invalide : {$file}\n");
    exit(1);
}

$metrics = $xml->project->metrics;

$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];
$methods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];

$statementsPercent = $statements === 0 ? 0.0 : ($coveredStatements / $statements) * 100;
$methodsPercent = $methods === 0 ? 0.0 : ($coveredMethods / $methods) * 100;

printf("Couverture backend : %.2f%% statements (%d/%d), %.2f%% méthodes (%d/%d)\n",
    $statementsPercent,
    $coveredStatements,
    $statements,
    $methodsPercent,
    $coveredMethods,
    $methods,
);

$ok = true;

if ($statementsPercent < $minStatements) {
    fwrite(STDERR, sprintf("Échec : statements %.2f%% < seuil %.2f%%\n", $statementsPercent, $minStatements));
    $ok = false;
}

if ($methodsPercent < $minMethods) {
    fwrite(STDERR, sprintf("Échec : méthodes %.2f%% < seuil %.2f%%\n", $methodsPercent, $minMethods));
    $ok = false;
}

if (! $ok) {
    exit(1);
}

echo "Seuils de couverture respectés.\n";
