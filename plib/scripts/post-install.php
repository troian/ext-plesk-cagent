<?php
/********************************************
 * Cagent monitoring Plugin for Plesk Panel
 * @Author:   Artur Troian troian dot ap at gmail dot com
 * @Author:   cloudradar GmbH
 * @Copyright: (c) 2019
*********************************************/

$result = pm_ApiCli::callSbin('installer');
if ($result['code'] !== 0) {
    throw new pm_Exception ('Error occurred when installing cagent package.');
}
