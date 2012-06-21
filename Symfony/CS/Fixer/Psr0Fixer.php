<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer;

use Symfony\CS\FixerInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Psr0Fixer implements FixerInterface
{
    public function fix(\SplFileInfo $file, $content)
    {
        $namespace = false;
        if (preg_match('{^namespace\s+(\S+)\s*;}um', $content, $match)) {
            $namespace = $match[1];
        }
        if (!preg_match('{^(class|interface|trait)\s+(\S+)}um', $content, $match)) {
            return $content;
        }

        $keyword = $match[1];
        $class = $match[2];

        if ($namespace) {
            $normNamespace = strtr($namespace, '\\', '/');
            $path = strtr($file->getRealPath(), '\\', '/');
            $dir = substr(dirname($path), -strlen($namespace));
            $filename = basename($path, '.php');
            if ($class !== $filename) {
                $content = preg_replace('{^'.$keyword.'\s+(\S+)}um', $keyword.' '.$filename, $content, 1);
            }
            if ($normNamespace !== $dir) {
                if (strtolower($normNamespace) === strtolower($dir)) {
                    $content = preg_replace('{^namespace\s+(\S+)\s*;}um', 'namespace '.strtr($dir, '/', '\\').';', $content, 1);
                } else {
                    echo '! The namespace '.$namespace.' in '.$path.' does not match the file path according to PSR-0 rules'.PHP_EOL;
                }
            }
        } else {
            $normClass = strtr($class, '_', '/');
            $path = strtr($file->getRealPath(), '\\', '/');
            $filename = substr($path, -strlen($normClass)-4, -4);

            if (!strpos($class, '_')) {
                echo '! Class '.$class.' in '.$path.' should have at least a vendor namespace according to PSR-0 rules'.PHP_EOL;
            }

            if ($normClass !== $filename) {
                if (strtolower($normClass) === strtolower($filename)) {
                    $content = preg_replace('{^'.$keyword.'\s+(\S+)}um', $keyword.' '.strtr($filename, '/', '_'), $content, 1);
                } else {
                    echo '! The class '.$class.' in '.$path.' does not match the file path according to PSR-0 rules'.PHP_EOL;
                }
            }
        }

        return $content;
    }

    public function getLevel()
    {
        return FixerInterface::PSR0_LEVEL;
    }

    public function getPriority()
    {
        return -10;
    }

    public function supports(\SplFileInfo $file)
    {
        if ('php' !== pathinfo($file->getFilename(), PATHINFO_EXTENSION)) {
            return false;
        }

        // ignore tests/stubs/fixtures, since they are typically containing invalid files for various reasons
        return !preg_match('{[/\\\\](test|stub|fixture)s?[/\\\\]}i', $file->getRealPath());
    }

    public function getName()
    {
        return 'psr0';
    }

    public function getDescription()
    {
        return 'Classes must be in a path that matches their namespace, be at least one namespace deep, and the class name should match the file name.';
    }
}
