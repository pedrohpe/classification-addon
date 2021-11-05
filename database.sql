CREATE TABLE IF NOT EXISTS `classaddon-badge` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`name` mediumtext,
`description` mediumtext,
`criteria` mediumtext,
`image` mediumtext,
PRIMARY KEY (`id`)
)
DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `classaddon-assertion` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`badge` int(11) unsigned NOT NULL,
`issuer_uid` mediumint unsigned NOT NULL,
`receiver_uid` mediumint unsigned NOT NULL,
`issued_date` timestamp NOT NULL,
`evidence` mediumtext,
PRIMARY KEY (`id`),
FOREIGN KEY (`badge`) REFERENCES `classaddon-badge` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
FOREIGN KEY (`issuer_uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
FOREIGN KEY (`receiver_uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
 )
DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO friendica.`classaddon-badge` (name,description,criteria,image) VALUES
	 ('Participante Frequente','Participante no fórum que mais realizou posts','Ser o participante com mais posts realizados no período definido','postador.png'),
	 ('Mais Curtido','O participante fez o post que recebeu mais curtidas no fórum','Post feito pelo participante teve mais curtidas que os outros posts','curtido.png'),
	 ('Comentador frequente','Participante do fórum que mais comentou nos posts de outros participantes','Ser o participante com o maior número de comentários no fórum','comentador.png'),
	 ('Participador Atrativo','Criador do posts com mais comentários no fórum','Recebeu mais comentários em seus posts que os outros alunos','participador.png');

