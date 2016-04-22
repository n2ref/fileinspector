<?php

if (PHP_SAPI === 'cli') {
    require_once 'classes/Inspector.php';
    require_once 'classes/Tools.php';

    $options = getopt('d:f:m:h', array(
        'dir:',
        'filename:',
        'mtime:',
        'help',
    ));


    if ((isset($options['d']) || isset($options['dir'])) &&
        (isset($options['f']) || isset($options['filename'])) &&
        (isset($options['m']) || isset($options['mtime']))
    ) {
        $dir      = isset($options['dir'])      ? realpath($options['dir']) : realpath($options['d']);
        $filename = isset($options['filename']) ? $options['filename']      : $options['f'];
        $mtime    = isset($options['mtime'])    ? $options['mtime']         : $options['m'];


        try {
            if ( ! is_dir($dir)) {
                throw new Exception("Incorrect parameter dir: Not found directory '{$dir}'");
            }
            if ( ! is_numeric($mtime)) {
                throw new Exception("Incorrect parameter mtime: Not valid number '{$mtime}'");
            }



            $inspector = new Inspector(__DIR__ . '/conf.ini');
            $files     = $inspector->fetchFiles($dir, $filename, $mtime);

            if ( ! empty($files)) {
                $file_warnings = $inspector->filterWarningFiles($files);

                if ( ! empty($file_warnings)) {
                    foreach ($file_warnings as $file => $warning) {
                        echo $file . ': ' . str_replace("\n", '', $warning) . PHP_EOL;
                    }



                    $config = $inspector->getConfig();

                    if ( ! empty($config['mail']) && ! empty($config['admin_email'])) {
                        $report_message = '<ol>';
                        foreach ($file_warnings as $file => $warning) {
                            $report_message .= '<li>' . $file . ': <b>' . str_replace("\n", '', $warning) . '</b></li>';
                        }
                        $report_message .= '</ol>';


                        $is_send = Tools::sendMail(
                            $config['admin_email'],
                            ! empty($config['email_subject']) ? $config['email_subject'] : 'Detected suspicious files',
                            $report_message,
                            $config['mail']
                        );

                        if ( ! $is_send) {
                            throw new Exception('Error send email');
                        }
                    }
                    echo PHP_EOL;
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

    } else {
        echo implode(PHP_EOL, array(
            'File inspector',
            'Usage: php inspector.php [OPTIONS]',
            'Required arguments:',
            "\t-d\t--dir\tInspection directory",
            "\t-f\t--filename\tFilename filter",
            "\t-m\t--mtime\t\tFile modification time (in days)",
            'Optional arguments:',
            "\t-h\t--help\t\tHelp info",
            "Examples of usage:",
            "php inspector.php -d /var/www/ -f '*.php' -m 10",
            "php inspector.php -d /var/www/ -f '*.php' -m 0.5",
        )) . PHP_EOL;
    }

    echo 'Done.' . PHP_EOL;

} else {
    echo 'Bad SAPI! Need cli.';
}