<?php 

use Friendica\DI;
use Friendica\App;

use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Session;

use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;

use Friendica\Module\BaseProfile;

use Friendica\Protocol\DFRN;

use Friendica\Database\DBA;

use Friendica\Model\Profile;
use Friendica\Model\Contact;

use Friendica\Util\Images;
use Friendica\Util\DateTimeFormat;

function classification_install(){
    Hook::register('profile_tabs', 'addon/classification/classification.php', 'class_tab_show');

	$schema = file_get_contents(dirname(__file__).'/database.sql');
	$arr = explode(';', $schema);
	foreach ($arr as $a) {
		$r = q($a);
	}

    Logger::info('Addon installed');
}

function classification_uninstall(){
    Hook::unregister('profile_tabs', 'addon/classification/classification.php', 'class_tab_show');
    Logger::info('Addon uninstalled');
}

function class_tab_show($a, &$b){

    $new = [
        'label' => 'Classification',
        'url' => 'classification/profile/' . $b['nickname'],
        'sel'   => !$b['tab'] && $a->argv[0] == 'classification' ? 'active' : '',
        'title' => 'Classification',
        'id' => 'classification-tab',
        'accesskey' => 'c',
    ];

    //mudar isso aqui pra adicionar no final do conjunto de tabs?
    $b['tabs'][] = array_splice($b['tabs'], 4, 0, [$new]);
}

function classification_module(){
}

function classification_init($a){
	//if ($a->argc > 1) {
	//	DFRN::autoRedir($a, $a->argv[1]);
	//}

    if ((DI::config()->get('system', 'block_public')) && !Session::isAuthenticated()) {
		return;
	}

    Nav::setSelected('home');
	Logger::debug($a->argv[1]);

    if ($a->argc > 2) {
		if($a->argv[1] == "profile"){
			$nick = $a->argv[2];

			/*$condition = [
				'uid' => local_user(), 'blocked' => false,
				'account_expired' => false, 'account_removed' => false
			];*/

			$user = DBA::selectFirst('user', [], ['nickname' => $nick, 'blocked' => false]);
			/*$user = DBA::selectFirst('user', [], $condition);*/

			if (!count($user))
				return;

			$a->data['user'] = $user;
			$a->profile_uid = $user['uid'];

			Logger::debug(json_encode($nick));
			Logger::debug(json_encode($a->profile_uid));

			$profile = Profile::getByNickname($nick, $a->profile_uid);
			if($profile == false){
				$profile = Profile::getByNickname($nick, 0);
			}
			
			Logger::debug(json_encode($profile));
			$account_type = Contact::getAccountType($profile);

			$tpl = Renderer::getMarkupTemplate("widget/vcard.tpl");


			$vcard_widget = Renderer::replaceMacros($tpl, [
				'$name' => $profile['name'],
				'$photo' => $profile['photo'],
				'$addr' => $profile['addr'] ?? '',
				'$account_type' => $account_type,
				'$about' => BBCode::convert($profile['about']),
			]);


			if (empty(DI::page()['aside'])) {
				DI::page()['aside'] = '';
			}

			DI::page()['aside'] .= $vcard_widget;
		}
    }
	Logger::debug('init AQUI2');
}

function classification_post(App $a){
	Logger::debug('POST AQUI');

	$badge_receiver_nick = ($_POST["badgereceiver-nick"]);
	$badge_receiver_evidence = ($_POST["badgereceiver-evidence"]);
	$badge_number = ($_POST["badgetypes"]);

	$user_receiver = DBA::selectFirst('user', [], ['nickname' => $badge_receiver_nick]);
	$user_issuer = DBA::selectFirst('user', ['nickname'], ['uid' => local_user()]);
	$evidence = $badge_receiver_evidence;

	Logger::debug($badge_number);
	Logger::debug(local_user());
	Logger::debug($user_receiver['uid']);

	$q = q("INSERT INTO `classaddon-assertion` 
	(`badge`, `issuer_uid`, `receiver_uid`, `issued_date`, `evidence`)
	VALUES (%d, %d, %d, now(), '" . $badge_receiver_evidence . "')", $badge_number, local_user(), $user_receiver['uid']);

	DI::baseUrl()->redirect('classification/profile/' . $user_issuer["nickname"]);
}

function query_forum_users($profile_id, $badge_id){

	switch($badge_id){
		case 1:
			$r = q("SELECT `post-view`.`parent-author-id` AS `pa-id`, 
			COUNT(`post-view`.`parent-author-id`) AS `posts-number` 
			FROM `post-view`
			WHERE `post-view`.`causer-id` = %d
			GROUP BY `pa-id` 
			ORDER BY `posts-number` DESC 
			LIMIT 1", intval($profile_id));

			Logger::debug(json_encode($r));

			
			$r2 = q("SELECT * from `contact` WHERE `contact`.`id` = %d AND `contact`.`uid` = 0", intval($r[0]['pa-id']));
			$rr2 = $r2[0];
			

			$badge_receiver = [
				'id' => $rr2['uid'],
				'image' => $rr2['thumb'],
				'nick' => $rr2['nick'],
				'name' => $rr2['name'],
				'evidence' => 'O estudante realizou ' . $r[0]['posts-number'] . ' posts no período especificado'
			];
			break;
		case 2:
			$r = q("SELECT a.`uri-id` AS `uri-id`, a.`author-id`,
			COUNT(a.`uri-id`) as `liked-posts`
			FROM `post-view` a
			INNER JOIN `post-view` b ON a.`uri-id` = b.`parent-uri-id`
			WHERE b.`vid` = 1 AND a.`causer-id` = %d
			GROUP BY `uri-id`
			ORDER BY `liked-posts` DESC
			LIMIT 1", intval($profile_id));

			Logger::debug(json_encode($r));

			$r2 = q("SELECT * from `contact` WHERE `contact`.`id` = %d AND `contact`.`uid` = 0", intval($r[0]['author-id']));
			$rr2 = $r2[0];

			$badge_receiver = [
				'id' => $rr2['uid'],
				'image' => $rr2['thumb'],
				'nick' => $rr2['nick'],
				'name' => $rr2['name'],
				'evidence' => 'O estudante teve um post com ' . $r[0]['liked-posts'] . ' curtidas no período especificado'
				
			];
			break;
		case 3:

			Logger::debug($profile_id);

			$r = q("SELECT  b.`author-id` AS `pa-id`, 
			COUNT(b.`author-id`) AS `comments-number` 
			FROM `post-view` a
			INNER JOIN `post-view` b ON a.`uri-id` = b.`parent-uri-id`
			WHERE a.`owner-id` = %d 
			AND b.`object-type`=  'http://activitystrea.ms/schema/1.0/comment'
			GROUP BY `pa-id` 
			ORDER BY `comments-number` DESC 
			LIMIT 1", intval($profile_id));

			Logger::debug(json_encode($r));

			$r2 = q("SELECT * from `contact` WHERE `contact`.`id` = %d AND `contact`.`uid` = 0", intval($r[0]['pa-id']));
			$rr2 = $r2[0];

			$badge_receiver = [
				'id' => $rr2['uid'],
				'image' => $rr2['thumb'],
				'nick' => $rr2['nick'],
				'name' => $rr2['name'],
				'evidence' => 'O estudante comentou ' . $r[0]['comments-number'] . ' vezes no período especificado'
				
			];
			break;
		case 4:
			$r = q("SELECT a.`uri-id` AS `uri-id`, a.`author-id`,
			COUNT(a.`uri-id`) as `commented-posts`
			FROM `post-view` a
			INNER JOIN `post-view` b ON a.`uri-id` = b.`parent-uri-id`
			WHERE b.`object-type`= 'http://activitystrea.ms/schema/1.0/comment' 
			AND a.`causer-id` = %d
			GROUP BY `uri-id`
			ORDER BY `commented-posts` DESC 
			LIMIT 1", intval($profile_id));

			Logger::debug(json_encode($r));

			$r2 = q("SELECT * from `contact` WHERE `contact`.`id` = %d AND `contact`.`uid` = 0", intval($r[0]['author-id']));
			$rr2 = $r2[0];

			$badge_receiver = [
				'id' => $rr2['uid'],
				'image' => $rr2['thumb'],
				'nick' => $rr2['nick'],
				'name' => $rr2['name'],
				'number' => $r[0]['commented-posts'],
				'evidence' => 'O estudante teve um post com ' . $r[0]['commented-posts'] . ' comentários no período especificado'
				
			];
			break;
	}

	$tpl = Renderer::getMarkupTemplate("member_result.tpl", "addon/classification/");

	Logger::debug("BADGE RECEIVER AQUI");
	Logger::debug(json_encode($badge_receiver));

	$content = '';
	$content .= Renderer::replaceMacros($tpl, [
		'$badge_receiver' => $badge_receiver
	]);

	Logger::debug($content);
	return $content;

}

function classification_content(App $a){
	Logger::debug('CONTENT AQUI');

	if ($a->argv[1] == "badgetype"){

		$nickname = $a->user["nickname"];
		$profile = DBA::selectFirst('contact', [], ['nick' => $nickname, 'uid' => 0]);

		$badge_id = $a->argv[2];
		$content = '';
		$content .= query_forum_users($profile["id"], $badge_id);
		return $content;
		
	} else {

		$content = '';

		$owner_uid = $a->data['user']['uid'];

		$is_owner = (local_user() && (local_user() == $owner_uid));

		$r = q("SELECT `contact`.`forum` AS `forum` FROM `contact` 
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1", intval($owner_uid));
		if(DBA::isResult($r)){
			foreach($r as $rr){
				$is_forum = $rr['forum'];
			}
		}

		if($is_forum && $is_owner){

			$tpl = Renderer::getMarkupTemplate("classification_forum.tpl", "addon/classification/");

			$badges = [];

			$r = q("SELECT * FROM `classaddon-badge`");

			if(DBA::isResult($r)){
				foreach($r as $rr){
					$badges[] = [
						'id' => $rr['id'],
						'name' => $rr['name']
					];
				}
			}

			$content .= BaseProfile::getTabsHTML($a, 'classification', $is_owner, $a->data['user']['nickname']);
			$content .= Renderer::replaceMacros($tpl, [
				'$title' => 'Badges',
				//'$show' => $is_owner ? '' : 'none',
				//'$view' => 'View badge',
				'$badgetypes' => $badges,
			]);

		} else {

			$tpl = Renderer::getMarkupTemplate("classification.tpl", "addon/classification/");

			$badges = call_user_func('get_badges_profile', $a, $is_owner, $owner_uid);

			$content .= BaseProfile::getTabsHTML($a, 'classification', $is_owner, $a->data['user']['nickname']);
			$content .= Renderer::replaceMacros($tpl, [
				'$title' => 'Badges',
				//'$show' => $is_owner ? '' : 'none',
				//'$view' => 'View badge',
				'$badges' => $badges,
			]);
		}
	

		return $content;
	}
}

function get_badges_profile(App $a, $is_owner, $uid){
	Logger::debug('BADGES AQUI');
	$badges = [];

	Logger::debug('uid:' . $uid);

	$r = q("SELECT `classaddon-assertion`.`id` AS `id`, `classaddon-assertion`.`issuer_uid` 
	AS `issuer_uid`, `classaddon-assertion`.`issued_date` AS `issued_date`, 
	`classaddon-assertion`.`evidence` AS `evidence`, `classaddon-badge`.`name` 
	AS `name`, `classaddon-badge`.`description` AS `description`, `classaddon-badge`.`criteria` 
	AS `criteria`, `classaddon-badge`.`image` AS `image` FROM `classaddon-assertion` INNER JOIN `classaddon-badge` 
	ON `classaddon-badge`.`id` = `classaddon-assertion`.`badge` 
	WHERE `classaddon-assertion`.`receiver_uid` = %d", intval($uid));


	Logger::debug(json_encode($r));

	$phototypes = Images::supportedTypes();


	if(DBA::isResult($r)){
		foreach($r as $rr){
			$issuer = DBA::selectFirst('user', ['nickname', 'username'], ['uid' => $rr['issuer_uid']]);

			$issuer_url = DI::baseUrl() . '/profile/' . $issuer['nickname'];
			

			//$image_url_r = DBA::selectFirst('photo', ['resource-id', 'scale', 'type'], ['id' => $rr['image']]);
			//$ext = $phototypes[$image_url_r['type']];
			//$image_url =  DI::baseUrl() . '/photo/' . $image_url_r['resource-id'] . '-' . $image_url_r['scale'] . '.' . $ext;

			$image_url = DI::baseUrl() . '/addon/classification/assets/' . $rr['image'];

			$badges[] = [
				'id' => $rr['id'],
				'image_url' => $image_url,
				'issuer' => $issuer['username'],
				'issuer_url' => $issuer_url,
				'issued_date' => $rr['issued_date'],
				'evidence' => $rr['evidence'],
				'name' => $rr['name'],
				'description' => $rr['description'],
				'criteria' => $rr['criteria']
			];
		}
	}

	return $badges;
}


?>