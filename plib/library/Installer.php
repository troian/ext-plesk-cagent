<?php

use Symfony\Component\Process\Process;

/********************************************
 * Cagent monitoring Plugin for Plesk Panel
 * @Author:   Artur Troian troian dot ap at gmail dot com
 * @Author:   cloudradar GmbH
 * @Copyright: (c) 2019
 *********************************************/
class Modules_Cagent_Installer
{
    protected $archType;
    protected $packageType;

    /**
     * Modules_Cagent_Installer constructor.
     */
    public function __construct()
    {
        if ('x86_64' == php_uname('m')) {
            $this->archType = 'amd64';
        } else {
            $this->archType = 'i386';
        }

        $process = new Process(['which', 'yum']);
        $process->run();
        if (!empty($process->getOutput())) {
            $this->packageType = 'rpm';
        }

        $process = new Process(['which', 'apt-get']);
        $process->run();
        if (!empty($process->getOutput())) {
            $this->packageType = 'deb';
        }
    }

    public function isInstalled()
    {
        if ('rpm' == $this->packageType) {
            $args = ['rpm', '-q', '--qf', '"%{VERSION}\n"', 'cagent'];
        } elseif ('deb' == $this->packageType) {
            $args = ['dpkg-query', '--showformat=\'${Version}\'', '--show','cagent'];
        } else {
            return new Modules_Cagent_Status(false, 'Package manager not found');
        }
        $process = new Process($args);
        $process->run();
        if (!$process->isSuccessful()) {
            return new Modules_Cagent_Status(false, $process->getErrorOutput());

        }

        return new Modules_Cagent_Status(true, $process->getOutput());
    }

    public function latest()
    {
        if('deb' == $this->packageType){
            return 'https://repo.cloudradar.io/pool/utils/c/cloudradar-release/cloudradar-release.deb';
        }

        return Modules_Cagent_Util::getLatestRelease($this->archType, $this->packageType);
    }


    public function install($params)
    {
        if (!$downloadUrl = $this->latest()) {
            throw new Exception("Unable to get cagent download url");
        }
        $filename = basename($downloadUrl);

        if ('rpm' == $this->packageType) {
            $temp_file = sys_get_temp_dir().DIRECTORY_SEPARATOR. $filename;
            $process = new Process(['wget','-O',$temp_file, $downloadUrl]);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new Exception($process->getErrorOutput());
            }
            $output = pm_ApiCli::callSbin('installer.php', [$this->packageType,$temp_file], pm_ApiCli::RESULT_FULL, [
                'CAGENT_HUB_URL'      => $params['url'],
                'CAGENT_HUB_USER'     => $params['user'],
                'CAGENT_HUB_PASSWORD' => $params['password']
            ]);
            if(file_exists($temp_file)){
                unlink($temp_file);
            }
            if ($output['code'] != 0) {
                throw new Exception($output['stderr']);
            }

        } else {
            $temp_file = sys_get_temp_dir().DIRECTORY_SEPARATOR. $filename;
            $process = new Process(['wget','-O',$temp_file, $downloadUrl]);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new Exception($process->getErrorOutput());
            }
            $output = pm_ApiCli::callSbin('installer.php', [$this->packageType,$temp_file], pm_ApiCli::RESULT_FULL, [
                'CAGENT_HUB_URL'      => $params['url'],
                'CAGENT_HUB_USER'     => $params['user'],
                'CAGENT_HUB_PASSWORD' => $params['password']
            ]);

            if(file_exists($temp_file)){
                unlink($temp_file);
            }
            if ($output['code'] != 0) {
                throw new Exception($output['stderr']);
            }
        }

        return $output['stdout'];
    }

    public function isRunning()
    {

    }

    public function uninstall()
    {
        $result = pm_ApiCli::callSbin('installer', ['uninstall',$this->packageType], pm_ApiCli::RESULT_FULL);
        $result['output'] = $result['stdout'];
        return $result;
    }
}