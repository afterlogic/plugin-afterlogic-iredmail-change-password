<?php

class_exists('CApi') or die();

CApi::Inc('common.plugins.change-password');

class CCustomChangePasswordPlugin extends AApiChangePasswordPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function validateIfAccountCanChangePassword($oAccount)
	{
		$account_host = strtolower(trim($oAccount->IncomingMailServer));
		$iredmail_hosts = CApi::GetConf('plugins.afterlogic-iredmail-change-password.config.hosts', array(
		    'localhost', '127.0.0.1', '::1', '::1/128', '0:0:0:0:0:0:0:1'
		));
		if (is_array($iredmail_hosts)) {
		    return in_array($account_host, $iredmail_hosts);
		}
		else
		{
		    return ($account_host === $iredmail_hosts);
		}
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function ChangePasswordProcess($oAccount)
	{
		$bResult = false;
		if (0 < strlen($oAccount->PreviousMailPassword) &&
			$oAccount->PreviousMailPassword !== $oAccount->IncomingMailPassword)
		{
			$iredmail_dbuser = CApi::GetConf('plugins.afterlogic-iredmail-change-password.config.dbuser');
			$iredmail_dbpass = CApi::GetConf('plugins.afterlogic-iredmail-change-password.config.dbpass');

			$mysqlcon=mysqli_connect('localhost', $iredmail_dbuser, $iredmail_dbpass, 'vmail');

		 	if($mysqlcon){
				
				$username = $oAccount->IncomingMailLogin;
				$password = $oAccount->IncomingMailPassword;

				$passhash = exec("doveadm pw -s 'ssha512' -p '".$password."'");

				$sql = "UPDATE mailbox SET password='".$passhash."' WHERE username='".$username."'";

				$result = mysqli_query($mysqlcon,$sql);
				if (!$result){
					throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
					}
				mysqli_close($mysqlcon);

			}else{
				throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
			}
		}
		return $bResult;
	}
}

return new CCustomChangePasswordPlugin($this);
