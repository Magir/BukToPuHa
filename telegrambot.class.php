<?php
class telegrambot{
	function __construct($token){
		$this->token=$token;
		if (file_exists('offset'.md5($token))){
			$this->offset=(int)file_get_contents('offset'.md5($token));
		}else{
			$this->offset=0;
		}
		$this->ch=curl_init();
		curl_setopt ($this->ch, CURLOPT_HEADER, false);
		curl_setopt ($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1;en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, 280);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, 'cookie_'.md5($token)); // CHANGE COOKIE FILE PATH IF NEEDED
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, 'cookie_'.md5($token)); // See previous line comment
		curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($this->ch, CURLOPT_COOKIESESSION, false);
		curl_setopt ($this->ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, true);
	}
	function getUpdates(){
		$updates=$this->request('getUpdates',array('offset'=>$this->offset));
		if (count($updates['result'])!=0){
			$this->offset=$updates['result'][count($updates['result'])-1]['update_id']+1;
			file_put_contents('offset'.md5($this->token),$this->offset);
			return $updates['result'];
		}
		return false;
	}
	function request($method,$params=array()){
		if (!$method) return;
		curl_setopt($this->ch, CURLOPT_POST, false);
		curl_setopt($this->ch,CURLOPT_URL,'https://api.telegram.org/bot'.$this->token.'/'.$method.(count($params)!=0?'?'.http_build_query($params):''));
		$ret=curl_exec($this->ch);
		if (debug==1) echo $ret;
		return json_decode($ret,1);
	}	
	function requestpost($method,$params=array()){
		if (!$method) return;
		curl_setopt($this->ch, CURLOPT_POST,1);
		curl_setopt($this->ch,CURLOPT_URL,'https://api.telegram.org/bot'.$this->token.'/'.$method);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
		$ret=curl_exec($this->ch);
		return json_decode($ret,1);
	}
	function send($chatid,$text,$reply_markup=array()){
		if (debug==1) echo 'Send '.$chatid.': '.$text."\n";
		$arr=array(
			'chat_id'=>$chatid,
			'text'=>$text,
		);
		if (count($reply_markup)>0)
			$arr['reply_markup']=json_encode($reply_markup);
		$this->request('sendMessage',$arr);
	}
	function sendimg($chatid,$img,$caption,$reply_markup=array()){
		if (debug==1) echo 'Send '.$chatid.': '.$img.' '.$caption."\n";
		$img=realpath($img);
		if (!file_exists($img)) return false;
		$arr=array(
			'chat_id'=>$chatid,
			'caption'=>$caption,
			'photo'=>'@'.$img
		);
		if (count($reply_markup)>0)
			$arr['reply_markup']=json_encode($reply_markup);
		$this->requestpost('sendPhoto',$arr);
	}

}

?>
