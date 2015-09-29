# BukToPuHa
Telegram bot @BukToPuHa (quiz, trivia)

# English description
Bot posts questions to playing accounts and waiting for answers. Players recevices all other players is posting to bot. Right answers adds 3 scores to player, answer after hint - 1 score.


Bot comands supported:

/start - enter to the game

/stop - quit from game

/top - best players

/mytop - player's scores

/feedback text - send a feedback to bote administrator

/reloadquiz - reload question base from file (admin-only)

/ban id - ban player by Telegram ID (admin-only)

/unban id - unban player by Telegram ID (admin-only)



Data storage - simple serialized files. 

Question list - text files (quiz and quiz2), questions and answers separated by asterix (*), image insert - three diez after question and filename (from images directory). Examples:

Country name###russia.gif*russia

Cat child*kitten



administrator_id in trivia.php - administrator's Telegram ID.

Bot includes question list in russian, images for questions from quiz2 - http://magir.ru/files/images.tar.gz

# Описание на русском языке
Бот задает вопросы на которые отвечают игроки. Игроки видят ответы друг-друга. Первый ответивший до подсказки получает 3 балла, после подсказки - 1 балл.


Поддерживаемые команды:

/start - войти в игру

/stop - выйти из игры

/top - лучшие игроки

/mytop - очки игрока

/feedback text - отправить сообщение администратору бота

/reloadquiz - перечитать файлы с вопросами (только администратор)

/ban id - забанить игрока по Telegram ID (только администратор)

/unban id - разбанить игрока по Telegram ID (только администратор)



Хранение данных - в простых файлах с сериализацией.

Список вопросов - текстовые файлы (quiz и quiz2), вопросы и ответы разделяются звездочкой (*), вставка изображений в вопросы - три решетки после вопроса. Примеры:

Назовите страну###russia.gif*россия

Ребенок кошки*котенок



е=ё в ответах

administrator_id в файле trivia.php - Telegram ID администратора.


Бот включает базу вопросов на русском языке, изображения для вопросов из quiz2 - http://magir.ru/files/images.tar.gz
