<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	require_once IA_INCLUDES . 'hybrid/Auth.php';

	switch ($iaView->name())
	{
		case 'hybrid':

			require_once IA_INCLUDES . 'hybrid/Endpoint.php';
			Hybrid_Endpoint::process();

			break;

		case 'login':

			$iaUsers = $this->factory('users');

			if ($iaCore->get('hybrid_enabled') && isset($iaCore->requestPath[0]) && $iaCore->requestPath[0])
			{
				try {

					$providerName = strtolower($iaCore->requestPath[0]);

					if (!file_exists(IA_INCLUDES . 'hybridauth.inc.php'))
					{
						throw new Exception("No HybridAuth config file. Please configure provider adapters.");
					}

					$hybridauth = new Hybrid_Auth(IA_INCLUDES . 'hybridauth.inc.php');

					if (empty(Hybrid_Auth::$config["providers"]))
					{
						throw new Exception("Please configure at least one adapter for HybridAuth.");
					}

					$provider = $hybridauth->authenticate(ucfirst($providerName));

					if ($user_profile = $provider->getUserProfile())
					{
						// identify by Hybrid identifier
						$memberId = $iaCore->iaDb->one('member_id', iaDb::convertIds($user_profile->identifier, 'value'), iaUsers::getProvidersTable());

						// identify by email address
						if (!$memberId)
						{
							if ($memberInfo = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, "`email` = :email_address", array('email_address' => $user_profile->email), iaUsers::getTable()))
							{
								$iaCore->iaDb->insert(array(
										'member_id' => $memberInfo['id'],
										'name' => $providerName,
										'value' => $user_profile->identifier
								), null, iaUsers::getProvidersTable());

								$memberId = $memberInfo['id'];
							}
						}

						// register new member if no matches
						if (!$memberId)
						{
							$memberRegInfo['username'] = '';
							$memberRegInfo['email'] = $user_profile->email;
							$memberRegInfo['fullname'] = $user_profile->displayName;
							// $memberRegInfo['avatar'] = $user_profile->photoURL;
							$memberRegInfo['disable_fields'] = true;

							$memberId = $iaUsers->register($memberRegInfo);

							// add providers match
							$iaCore->iaDb->insert(array(
									'member_id' => $memberId,
									'name' => $providerName,
									'value' => $user_profile->identifier
							), null, iaUsers::getProvidersTable());

							// no need to validate address
							$iaUsers->update(array('id' => $memberId, 'sec_key' => '', 'status' => iaCore::STATUS_ACTIVE));
						}

						// authorize
						$iaUsers->getAuth($memberId);
					}
					else
					{
						throw new Exception("User is not logged in.");
					}
				}
				catch (Exception $e)
				{
					$iaCore->iaView->setMessages("HybridAuth error: " . $e->getMessage(), iaView::ERROR);
				}
			}

			if (iaUsers::hasIdentity())
			{
				$iaPage = $iaCore->factory('page', iaCore::FRONT);

				$iaCore->factory('util')->go_to($iaPage->getUrlByName('profile'));
			}

			if (isset($_SERVER['HTTP_REFERER']) && IA_SELF != $_SERVER['HTTP_REFERER']) // used by login redirecting mech
			{
				$_SESSION['referrer'] = $_SERVER['HTTP_REFERER'];
			}

			break;
	}
}