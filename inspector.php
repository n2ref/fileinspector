<?php

if (PHP_SAPI === 'cli') {
    require_once 'classes/Inspector.php';
    require_once 'classes/Tools.php';

    $options = getopt('d:f:m:u:g:h', array(
        'dir:',
        'filename:',
        'mtime:',
        'user:',
        'group:',
        'help',
    ));


    if ((isset($options['d']) || isset($options['dir'])) &&
        (isset($options['f']) || isset($options['filename'])) &&
        (isset($options['m']) || isset($options['mtime'])) &&
        ( ! isset($options['h']) && ! isset($options['help']))
    ) {
        $dir      = isset($options['dir'])      ? realpath($options['dir']) : realpath($options['d']);
        $filename = isset($options['filename']) ? $options['filename']      : $options['f'];
        $mtime    = isset($options['mtime'])    ? $options['mtime']         : $options['m'];

        $user = isset($options['user'])
            ? $options['user']
            : (isset($options['u']) ? $options['u'] : '');
        $group = isset($options['group'])
            ? $options['group']
            : (isset($options['g']) ? $options['g'] : '');

        try {
            if ( ! is_dir($dir)) {
                throw new Exception("Incorrect parameter dir: Not found directory '{$dir}'");
            }
            if ( ! is_numeric($mtime)) {
                throw new Exception("Incorrect parameter mtime: Not valid number '{$mtime}'");
            }



            $inspector = new Inspector(__DIR__ . '/conf.ini');
            $files     = $inspector->fetchFiles($dir, $filename, $mtime, $user, $group);

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
                            $report_message .= '<li>' . $file . ': <b>' . str_replace("\n", '', htmlspecialchars($warning)) . '</b></li>';
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
            "\t-d\t--dir\t\tInspection directory",
            "\t-f\t--filename\tFilename filter",
            "\t-m\t--mtime\t\tFile modification time (in days)",
            'Optional arguments:',
            "\t-u\t--user\t\tFind file owned by user",
            "\t-g\t--group\t\tFind the file belongs to group-name.",
            "\t-h\t--help\t\tHelp info",
            "Examples of usage:",
            "php inspector.php -d /var/www/ -f '*.php' -m 10",
            "php inspector.php -d /var/www/ -f '*.php' -m 0.5 -u www-data",
        )) . PHP_EOL;
    }

    echo 'Done.' . PHP_EOL;

} else {
    echo 'Bad SAPI! Need cli.';
}