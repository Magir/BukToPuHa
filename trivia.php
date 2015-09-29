<?php
// Run this file using screen to start bote, be sure that PHP can write in directory, where this file is located
define('debug',0); // Debug mode, debug info posted to console
define('administrator_id',30406413); // Change administrator_id to your Telegram ID
define('bote_id','123:ABC'); // Change this to your bot ID, get bot ID from BotFather (https://core.telegram.org/bots)
global $bot;
global $players;
global $allplayers;
global $top;
global $game;
global $banlist;
global $sendall;
$sendall='';
$players=array();
$allplayers=array();
$top=array();
if (file_exists('top')){
	$top=unserialize(file_get_contents('top'));
}
if (file_exists('players')){
	$players=unserialize(file_get_contents('players'));
}
if (file_exists('allplayers')){
	$allplayers=unserialize(file_get_contents('allplayers'));
}
if (file_exists('banlist')){
	$banlist=unserialize(file_get_contents('banlist'));
}

global $questionlist;
$questions=file_get_contents('quiz');
$questions.="\n".file_get_contents('quiz2');
$questionlist=explode("\n",trim($questions));

$game=array(
	'state'=>0, // 0 - пауза между вопросами, 1 - задан вопрос
	'timer'=>0, // таймер
	'last'=>time(), // последнее действие (отсчет секунд)
	'question'=>'',
	'answer'=>'',
	'pod'=>0,
);

require('telegrambot.class.php');
$bot=new telegrambot(bote_id);
while(1){
	$messages=$bot->getUpdates();
	if ($messages!=false){
		foreach($messages as $msg){
			if ($msg['message']['text'] && $msg['message']['from']['id']==$msg['message']['chat']['id']){
				if (debug==1) echo 'Got text: '.$msg['message']['text']."\n";
				$from=$msg['message']['from'];
				$text=$msg['message']['text'];
				switch ($text){
					case '/start':
					case '/старт':
					case '/Старт':
					case '/Start':
						start($from['id'],$from['first_name'],$from['last_name']);
					break;
					case '/stop':
					case '/end':
					case '/стоп':
					case '/Стоп':
					case '/Stop':
					case '/quit':
					case '/exit':
						stop($from['id']);
					break;
					case '/help':
						help($from['id']);
					break;
					case '/top':
						top($from['id']);
					break;
					case '/mytop':
						mytop($from['id']);
					break;
					case '/reloadquiz':
						if ($from['id']==administrator_id){
							global $questionlist;
							$questions=file_get_contents('quiz');
							$questions.="\n".file_get_contents('quiz2');
							$questionlist=explode("\n",trim($questions));
						}
					break;
					default:
						if (mb_stripos($text,'/ban',0,'UTF-8')!==FALSE && $from['id']==administrator_id){
							ban(mb_substr($text,5,mb_strlen($text,'UTF-8'),'UTF-8'));
						}else
						if (mb_stripos($text,'/unban',0,'UTF-8')!==FALSE && $from['id']==administrator_id){
							unban(mb_substr($text,7,mb_strlen($text,'UTF-8'),'UTF-8'));
						}else
						if (mb_stripos($text,'/feedback',0,'UTF-8')!==FALSE){
							feedback($from['id'],mb_substr($text,10,mb_strlen($text,'UTF-8'),'UTF-8'));
						}else
						message($from['id'],$text);
					break;
				}
			}
		}
	}
	process();
	flushlog();
	usleep(500000);
}

function feedback($userid,$text){
	global $bot;
	$text=iconv('utf-8','windows-1251',$text);
	if (strlen($text)>3){
		file_put_contents('feedback',"\n".date('d.m.Y H:i:s '.$userid.': '.$text),FILE_APPEND);
		$bot->send($userid,'Ваше сообщение записано. Спасибо!'); // "Your message wrote. Thanks!"
	}else{
		$bot->send($userid,'Нет текста отзыва, используйте команду правильно - "/feedback текст" (без кавычек).'); // "No feedback text, use command right - /feedback text"
	}
}

function ban($userid){
	global $allplayers,$bot;
	if (isset($allplayers[$userid])){
		global $banlist;
		$banlist[$userid]=1;
		stop($userid);
		file_put_contents('banlist',serialize($banlist));
		$bot->send(30406413,'Игрок '.$userid.' заблокирован, счет аннулирован.'); // "Player ... banned, score nulled."
		$top[$userid]=0;
		$bot->send($userid,'Вы заблокированы администратором, ваши очки сборшены.'); // "You was blocked by administrator, your score nulled."
	}else{
		$bot->send(30406413,'Игрок '.$userid.' не найден в списке игроков.'); // "Player ... was not found in players list."
	}
}

function unban($userid){
	global $allplayers,$bot,$banlist;
	if (isset($allplayers[$userid]) && isset($banlist[$userid])){
		unset($banlist[$userid]);
		file_put_contents('banlist',serialize($banlist));
		$bot->send(30406413,'Игрок '.$userid.' разблокирован.'); // "Player ... unbanned."
		$bot->send($userid,'Вы разблокированы администратором.'); // "You was unbanned by administrator"
	}else{
		$bot->send(30406413,'Игрок '.$userid.' не найден в списке игроков/бана.'); // "Player ... was not found in ban list."
	}
}

function process(){
	global $game,$players;
	if (count($players)==0) return;
	if ($game['state']==0){
		// пауза между вопросами
		if (time()>$game['last']+$game['timer']){
			// переключение статуса - задать вопрос
			$game['state']=1;
			$game['timer']=60;
			$game['last']=time();
			$q=getquestion();
			$game['question']=$q[0];
			$game['answer']=$q[1];
			$game['pod']=0;
			flushlog();
			$text=$game['question'];
			if (mb_strpos($text,'###')){
				$text=explode('###',$text);
				sendallimg('images/'.$text[1],'⁉️ Вопрос: '.$text[0]); // "Question: .."
			}else{
				sendall('⁉️ Вопрос: '.$text);
			}
			flushlog();
			file_put_contents('logs/'.date('d.m.y'),date('H:i:s').' Вопрос! '.$game['question']."\n",FILE_APPEND);
		}
	}else{
		if (time()>$game['last']+$game['timer']){
			// время истекло, никто не ответил
			$game['state']=0;
			$game['timer']=10;
			$game['last']=time();
			flushlog();
			sendall('Никто не ответил на вопрос. Следующий вопрос через 10 секунд.'); // "Nobody answers. Next question in 10 seconds" 
			// you can add this $game['answer'] to previous line to show right answer
			flushlog();
		}else if ((time()>$game['last']+15) && $game['pod']==0){
			$game['pod']=1;
			// выдать подсказку 1
			$pod='';
			$pod.=mb_substr($game['answer'],0,1,'UTF-8');
			for ($i=1;$i<mb_strlen($game['answer'],'UTF-8')-1;$i++){
				$pod.='*';
			}
			$pod.=mb_substr($game['answer'],mb_strlen($game['answer'],'UTF-8')-1,1,'UTF-8');
			sendall('❕ Подсказка: '.$pod.'. Следующая подсказка - через 30 секунд.'); // "Hint: ... Next hint in 30 seconds"
			flushlog();
		}else if ((time()>$game['last']+45) && $game['pod']==1){
			$game['pod']=2;
			// выдать подсказку 2
			$pod='';
			$pod.=mb_substr($game['answer'],0,2,'UTF-8');
			for ($i=2;$i<mb_strlen($game['answer'],'UTF-8')-1;$i++){
				if ($i==4) $pod.=mb_substr($game['answer'],4,1,'UTF-8');
				else if ($i==7) $pod.=mb_substr($game['answer'],7,1,'UTF-8');
				else if ($i==9) $pod.=mb_substr($game['answer'],9,1,'UTF-8');
				else if ($i==12) $pod.=mb_substr($game['answer'],12,1,'UTF-8');
				else $pod.='*';
			}
			$pod.=mb_substr($game['answer'],mb_strlen($game['answer'],'UTF-8')-1,1,'UTF-8');
			sendall('❕ Подсказка: '.$pod.'.'); // "Hint: ..."
			flushlog();
		}
	}
}

function sendallimg($img,$caption){
	global $players,$bot;
	foreach ($players as $id=>$misc){
		$bot->sendimg($id,$img,$caption);
	}
	if (debug==1) echo 'Send all img: '.$img.' '.$caption."\n";
}

function sendall($text,$except=0){
	global $sendall;
	$sendall.=$text."\n";
}

function flushlog(){
	global $sendall;
	if ($sendall!=''){
		global $players,$bot;
		foreach ($players as $id=>$misc){
			$bot->send($id,$sendall);
		}
		$sendall='';
		if (debug==1) echo 'Send all: '.$sendall."\n";
	}
}

function message($userid,$text){
	global $game;
	global $players;
	global $top;
	global $bot;
	if (isset($players[$userid])){
		file_put_contents('logs/'.date('d.m.y'),date('H:i:s').' '.$userid.' '.$players[$userid][0].' '.$players[$userid][1].': '.$text."\n",FILE_APPEND);
		sendall($players[$userid][0].' '.$players[$userid][1].': '.$text,$userid);
		if ($game['state']==1){
			// задан вопрос - сравним ответ
			if (mb_stripos(mb_strtolower($text,'UTF-8'),$game['answer'],0,'UTF-8')!==FALSE){
				$game['state']=0;
				$game['timer']=5;
				$game['last']=time();	
				if ($game['pod']==0){
					$prize=3;
				}else{
					$prize=1;
				}
				$top[$userid]+=$prize;
				sendall('‼️ Правильный ответ - "'.$game['answer'].'". '.$players[$userid][0].' '.$players[$userid][1].' получает '.$prize.' балл'.($prize>1?'а':'').', общий счет игрока - '.$top[$userid]); // "Right answer - ..., gets ... score, total player scores - ..."
				flushlog();
				file_put_contents('top',serialize($top));
			}
		}
	}else{
		$bot->send($userid,'Наберите /start чтобы войти в игру.'); // "Send /start command to enter the game."
	}
}

function start($userid,$first_name,$last_name){
	global $banlist,$bot,$players;
	global $allplayers,$game,$top;
	if (!isset($players[$userid])){
		if (!isset($banlist[$userid])){
			$players[$userid]=array($first_name,$last_name);
			$allplayers[$userid]=array($first_name,$last_name);
			file_put_contents('allplayers',serialize($allplayers));
			file_put_contents('players',serialize($players));
			//sendall('Приходит '.$allplayers[$userid][0].' '.$allplayers[$userid][1].' ('.(int)$top[$userid].' баллов).'); 
			$bot->send($userid,'Вы вошли в игру. Читайте вопросы и пишите ответы! Чтобы выйти из игры наберите /stop'); // "You've entered game. Read questions and write answers. To leave game send /stop"
			$bot->send($userid,'Сейчас в игре '.count($players).' игроков.');
			if ($game['state']==1){
				$bot->send($userid,'Текущий вопрос: '.$game['question']);
			}
		}else{
			$bot->send($userid,'Вы заблокированы в игре, можете оставить сообщение администратору командой "/feedback текст" (без кавычек).'); // "You was blocked"
		}
	}
}

function stop($userid){
	global $players,$allplayers,$bot,$top;
	if (isset($players[$userid])){
		$bot->send($userid,"Вы вышли из игры. Чтобы вернуться в игру наберите /start\nЕсли вам нравится BukToPuHa - оцените бота в общем рейтинге - https://telegram.me/storebot?start=BukToPuHabot"); // "You've left game. To enter again write /start "
		unset($players[$userid]);
		//sendall($allplayers[$userid][0].' '.$allplayers[$userid][1].' ('.(int)$top[$userid].' баллов) уходит из игры.');
		file_put_contents('players',serialize($players));
	}
}


function help($userid){
	global $bot,$players,$questionlist;
	$bot->send($userid,"BukToPuHa v.0.1\nБот задает вопросы на которые отвечают игроки. Игроки видят ответы друг-друга. Первый ответивший до подсказки получает 3 балла, после подсказки - 1 балл.\nСейчас в игре: ".count($players)." игроков.\nВопросов в базе: ".count($questionlist).".\nКоманды бота:\n/start - войти в игру\n/stop - выйти из игры\n/top - лучшие игроки\n/mytop - ваши очки\nОтправить сообщение разработчику - /feedback текст\n\nВ игре не приветствуется мат и неадекватное поведение, злостные нарушители будут блокироваться.\n\nРазработчик - @ivanboytsov"); // This is help-message, use README.md or your own text here.
}

function mytop($userid){
	global $bot;
	global $top;
	arsort($top);
	$xtop=array_keys($top);
	$place=array_search($userid,$xtop);
	$bot->send($userid,'У вас '.(int)$top[$userid].' очков, место в рейтинге - '.($place+1)); // "You've got ... scores, place in top - ..."
}

function top($userid){
	global $bot;
	global $top,$allplayers;
	arsort($top);
	$out='';
	$i=0;
	foreach ($top as $k=>$p){
		$out.=($i+1).'. '.$allplayers[$k][0].' '.$allplayers[$k][1].' - '.$p."\n";
		$i++;
		if ($i==20) break;
	}
	$bot->send($userid,$out);
}

function getquestion(){
	global $questionlist;
	$one=explode('*',$questionlist[array_rand($questionlist)]);
	$one[0]=mb_ucfirst($one[0]);
	$one[1]=trim($one[1]);
	return $one;
}

function mb_ucfirst($str) { 
    return mb_substr(mb_strtoupper($str,'utf-8'),0,1,'utf-8').mb_strtolower(mb_substr($str,1,mb_strlen($str,'utf-8'),'utf-8'),'utf-8');
} 

?>
