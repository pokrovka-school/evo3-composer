<?php

namespace ProjectSoft;

class PrepareForm {

	public static function prepareProcess($modx, $data, $FormLister, $name)
	{
		$cfg = $FormLister->config->getConfig();
		$site = $modx->config['site_name'];
		$FormLister->mailConfig['subject']  = $cfg["subject"] = "Робот сайта «" . $site . "»";
		$FormLister->mailConfig['replyTo']  = $cfg["replyTo"] = $modx->config['email_bot'];
		$FormLister->mailConfig['fromName']  = $cfg["fromName"] = $modx->config['email_bot_name'];
		$FormLister->config->setConfig($cfg);
	}

	public static function prepare($modx, $data, $FormLister, $name)
	{
		$id = $modx->documentIdentifier;
		$url = $modx->makeUrl($id, '', '', 'full');
		$charset = $modx->config['modx_charset'];
		$message = $FormLister->getField('message');
		$message = $message ? htmlspecialchars(trim(strip_tags($message)), ENT_COMPAT, $charset, true) : '';
		$FormLister->setField('message', nl2br($message));
		$FormLister->setField("pagetitle", $modx->documentObject["pagetitle"]);
		$FormLister->setField("url", $url);
	}

	public static function prepareAfterProcess($modx, $data, $FormLister, $name)
	{
		/**
		 * Отправка в бот телеграма
		 * Отключено. Телеграм в России не работает
		 * Но пока оставим...
		 */
		$theme = $FormLister->getField("formid");
		$theme_val = "Вопрос с сайта " . $modx->config['site_name'];
		$message = $FormLister->getField('message');
		$message = $message ? $message : '';

		$page = '' . $modx->documentObject["pagetitle"] . " _" . $FormLister->getField('url') . "_";

		$first_name = $FormLister->getField('first_name');

		$email = $FormLister->getField('email');

		$phone = $FormLister->getField('phone');

		$date = date('d.m.Y H:i:s', time() + $modx->config['server_offset_time']);

		$arr = array(
			"types" => array(
				'date'		=> 'Дата',
				'theme'		=> 'Тема',
				'name'		=> 'Имя',
				'email'		=> 'Email',
				'phone'		=> 'Телефон',
				'message'	=> 'Сообщение',
				'url'		=> 'Страница отправки'
			),
			"fields" => array(
				'date'		=> date('d.m.Y H:i:s', time() + $modx->config['server_offset_time']),
				'theme'		=> $theme_val,
				'name'		=> $first_name,
				'email'		=> $email,
				'phone'		=> $phone,
				'message'	=> $message,
				'url'		=> $page
			),
			"parse_mode"	=> "Markdown",
			"tlg_token"		=> $modx->config["tlg_token"],
			"chat_id"		=> $modx->config["tlg_chanel"]
		);
		/**
		$bot = new \ProjectSoft\SendBot($arr);
		$result = $bot->send();
		$json = json_decode($result);
		if(is_object($json)):
			if(!$json->ok):
				$FormLister->setFormStatus(false);
				$FormLister->addMessage($json->description);
				//$FormLister->setFormStatus(false);
			endif;
		else:
			$FormLister->setFormStatus(false);
			$FormLister->addMessage($result);
			//$FormLister->setFormStatus(false);
		endif;
		*/
		/**
		 * Далее прикрутить API для отправки в другой бот
		 */
	}
}
