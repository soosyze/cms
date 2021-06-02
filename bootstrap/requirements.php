<?php

$requiement = new Requiement();
$require    = $requiement
    ->versionPhp('5.5')
    ->memoryLimit(8)
    ->extensions(array(
        'date', 'fileinfo', 'filter', 'gd', 'json', 'mbstring', 'openssl', 'session', 'zip'
    ));

if (!$require->isValid()) {
    echo $require;
    exit();
}

class Requiement
{
    /**
     * @var bool
     */
    protected $error;

    /**
     * @var bool
     */
    protected $warning;

    /**
     * @var array
     */
    protected $requiements = array();

    /**
     * @var array
     */
    protected $tests = array();

    public function __toString()
    {
        $html = '<!DOCTYPE html>
        <html lang=\'en\'>
        <head>
            <meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\'>
            <meta content=\'IE=edge\' http-equiv=\'X-UA-Compatible\'>
            <title>site</title>
            <style>
            table{width: 100%; border-collapse: collapse; margin-bottom: 3px;}
            thead{background-color: #272822; color:#FFF;}
            thead th{padding: 10px; border-bottom: 3px solid #FFF;}
            th{border:0px;}
            .table-trace td{border-left: #FFF 3px solid;}
            .table-trace tr:hover td,
            .table-trace tr:hover th{background-color: #272822;}
            .table-trace th,
            .table-trace td{padding: 10px;}
            .two th,
            .two td{background-color: #E9E9E9;}
            .error th,
            .error td{color: #be3232;}
            .warning th,
            .warning td{color: #bc9533;}
            .success th,
            .success td{color: #33bc36;}
            </style>
        </head>
        <body>
            <div style=\'width: 80%; margin-left: auto; margin-right: auto; overflow-x:auto\'>
                <h1 style=\'color: #be3232; text-align:center;\'>✘ Error Required</h1>
                <table class=\'table-trace\'>
                    <thead>
                        <tr>
                            <th>Validité</th>
                            <th>Type</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($this->requiements as $key => $value) {
            $class = $key % 2
                ? 'two'
                : 'one';
            $html  .= "<tr class='$class {$value[ 'type' ]}'>";
            if ($value[ 'type' ] === 'error') {
                $html .= '<th>✗</th>';
            } elseif ($value[ 'type' ] === 'warning') {
                $html .= '<th>!</th>';
            } else {
                $html .= '<th>✓</th>';
            }
            $html .= "<td>{$value[ 'name' ]}</td>
                    <td>{$value[ 'message' ]}</td>
                </tr>";
        }
        return $html . '</tbody>
                </table>
            </div>
        </body>
        </html>';
    }

    /**
     * @param string $version
     * @param string $operator
     *
     * @return $this
     */
    public function versionPhp($version, $operator = '>=')
    {
        $this->tests[] = array(
            'func' => __FUNCTION__,
            'args' => array(
                htmlspecialchars(strtolower($version)),
                $operator
            ));

        return $this;
    }

    /**
     * @param array $extensions
     *
     * @return $this
     */
    public function extensions($extensions)
    {
        $this->tests[] = array(
            'func' => __FUNCTION__,
            'args' => array( $extensions )
        );

        return $this;
    }

    /**
     * @param int $size
     * @param string $unit
     *
     * @return $this
     */
    public function memoryLimit($size = 128, $unit = 'MB')
    {
        $this->tests[] = array(
            'func' => __FUNCTION__,
            'args' => array(
                $size,
                $unit
            ));

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->error   = false;
        $this->warning = false;
        foreach ($this->tests as $test) {
            $callable = array( $this, 'valid' . $test[ 'func' ] );
            if (!is_callable($callable)) {
                continue;
            }
            call_user_func_array($callable, $test[ 'args' ]);
        }

        return empty($this->error) && empty($this->warning);
    }

    /**
     * @param string $version
     * @param string $operator
     *
     * @return void
     */
    protected function validVersionPhp($version, $operator = '>=')
    {
        $phpVersion = phpversion();
        if (!function_exists('version_compare') || $phpVersion === false) {
            $this->addReturn('versionphp', 'warning', 'PHP version', 'La version ne peut être comparée.');
        } elseif (version_compare($phpVersion, $version, $operator) === false) {
            $this->addReturn('versionphp', 'error', 'PHP version', 'La version PHP :version :operator est attendue. Vous êtes actuellement en version :current_version', array(
                ':version'         => htmlspecialchars($version),
                ':operator'        => htmlspecialchars($operator),
                ':current_version' => $phpVersion
            ));
        } else {
            $this->addReturn('versionphp', 'success', 'PHP version', 'La version PHP :version_current est ok.', array(
                ':version_current' => $phpVersion
            ));
        }
    }

    /**
     * @param array $extensions
     *
     * @return void
     */
    protected function validExtensions($extensions = array())
    {
        foreach ($extensions as $value) {
            if (!function_exists('extension_loaded')) {
                // Ne peu pas tester les extension chargées
                $this->addReturn('extension', 'warning', 'PHP extensions', 'Les extensions ne peuvent être testées.');
            } elseif (!extension_loaded($value)) {
                $this->addReturn('extension', 'error', 'PHP extensions', 'L\'extension <code>:extension</code> ne doit pas être désactivée.', array(
                    ':extension' => htmlspecialchars($value)
                ));
            } else {
                $this->addReturn('extension', 'success', 'PHP extensions', 'L\'extension <code>:extension</code> est activée.', array(
                    ':extension' => htmlspecialchars($value)
                ));
            }
        }
    }

    /**
     * @param int $size
     * @param string $bytes
     *
     * @return void
     */
    protected function validMemoryLimit($size = 128, $bytes = 'MB')
    {
        if (!function_exists('ini_get')) {
            $this->addReturn('memory', 'warning', 'PHP memory limit', 'La mémoire requise ne peut être testée.');

            return;
        }
        $memory = ini_get('memory_limit');
        if ($memory === false) {
            $this->addReturn('memory', 'warning', 'PHP memory limit', 'La configuration memory_limit n\'existe pas.');
        } elseif ($memory === '') {
            $this->addReturn('memory', 'warning', 'PHP memory limit', 'La configuration memory_limit est vide.');
        }
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $name
     * @param string $message
     * @param array $args
     *
     * @return void
     */
    private function addReturn($key, $type, $name, $message, $args = array())
    {
        $msg = str_replace(array_keys($args), $args, $message);
        array_push($this->requiements, array(
            'key'     => $key,
            'type'    => $type,
            'name'    => $name,
            'message' => $msg
        ));
        if ($type === 'error' && $this->error === false) {
            $this->error = true;
        } elseif ($type === 'warning' && $this->warning === false) {
            $this->warning = true;
        }
    }
}
