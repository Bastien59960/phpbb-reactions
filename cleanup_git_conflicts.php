<?php
/**
 * Script de nettoyage des conflits Git
 * Supprime tous les marqueurs de conflit Git des fichiers
 */

function cleanGitConflicts($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    $conflictMarkers = [
        '<<<<<<< HEAD',
        '=======',
        '>>>>>>> '
    ];
    
    $cleanedFiles = [];
    
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'yml', 'yaml', 'html', 'css', 'js'])) {
            $content = file_get_contents($file->getPathname());
            $originalContent = $content;
            
            // Supprimer les marqueurs de conflit
            foreach ($conflictMarkers as $marker) {
                $content = str_replace($marker, '', $content);
            }
            
            // Nettoyer les lignes vides multiples
            $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
            
            if ($content !== $originalContent) {
                file_put_contents($file->getPathname(), $content);
                $cleanedFiles[] = $file->getPathname();
                echo "Nettoyé: " . $file->getPathname() . "\n";
            }
        }
    }
    
    return $cleanedFiles;
}

echo "=== Nettoyage des conflits Git ===\n";
$cleanedFiles = cleanGitConflicts('.');
echo "\n=== Résumé ===\n";
echo "Fichiers nettoyés: " . count($cleanedFiles) . "\n";

if (count($cleanedFiles) > 0) {
    echo "\nFichiers modifiés:\n";
    foreach ($cleanedFiles as $file) {
        echo "- $file\n";
    }
} else {
    echo "Aucun conflit Git trouvé.\n";
}

echo "\n=== Vérification de la syntaxe PHP ===\n";
$phpFiles = glob('*.php');
$phpFiles = array_merge($phpFiles, glob('*/*.php'));
$phpFiles = array_merge($phpFiles, glob('*/*/*.php'));

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = [];
        $return = 0;
        exec("php -l \"$file\" 2>&1", $output, $return);
        
        if ($return !== 0) {
            echo "ERREUR dans $file:\n";
            echo implode("\n", $output) . "\n\n";
        } else {
            echo "✓ $file\n";
        }
    }
}

echo "\n=== Nettoyage terminé ===\n";
?>
