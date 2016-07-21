<?php

require_once(__CONTROLLERS_DIR__.'FakeUser.php');
require_once(__CONTROLLERS_DIR__.'RiotAPI.php');
$api = new RiotAPI();

$_response=Array();
if(isset($_GET['action'])){
  extract($_POST);
  switch($_GET['action']):
    case 'signup':
      switch($_GET['subject']){
        case 'summoner':
          // 1 - ok
          // 2 - já cadastrado
          // 3 - não existe
          $signal=0;
          $summonerNameUrl = 'https://br.api.pvp.net/api/lol/br/v1.4/summoner/by-name/__name__?api_key=2a0a5c1e-7355-42dc-8e2b-f25d5ee9771f';
          $summonerName = str_replace('__name__',strip_tags($_POST['name']),$summonerNameUrl);
          $summonerName = json_decode(@file_get_contents($summonerName));
          if(!empty($summoner)){
            if($summoner=='503'){
              $signal=503;
            }
            else{
              $_response['signup']['username'] = removeAccents(key($summoner));
            }
          }
          else{$signal=3;}
          //
          pr($_response['signup']['username']);
          pr($_response['signup']['summoner']);
          $_response['signup']['summoner'] = $signal;
        break;
        case 'validate':
          $name = strip_tags($_POST['name']);
          $server = $_POST['server'];
          $password = strip_tags($_POST['password']);
          $passwordConfirm = strip_tags($_POST['passwordConfirm']);
          $email = $_POST['email'];
          $emailConfirm	= $_POST['emailConfirm'];
          $sex = $_POST['sex'];
          $summoner	=json_decode($api->getSummonerByName($name));
          if(!empty($summoner)){
            if($summoner=='503'){
              $_response['signup']['validate']['failed']['api'] ='API RIOT Indisponível.';
            }
            else{
              // $mysql->Select('user',array('username'=>removeAccents(key($summoner))));
              if($mysql->iRecords==0){
                $username=(key($summoner));
              }else{
                $_response['signup']['validate']['failed']['username'	] = 'Usuário já existe.';
              }
            }
          }
          else{
            $_response['signup']['validate']['failed']['name'] = 'Nome não existe.';
          }
          if(strlen($password)<6){
            $_response['signup']['validate']['failed']['password'] = 'Senha menor do que 6 caracteres.'	;
          }
          if($password !== $passwordConfirm){
            $_response['signup']['validate']['failed']['password'] = 'Senhas não conferem.';
          }
          if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $_response['signup']['validate']['failed']['email'] = 'Email inválido.';
          }
          if($email !== $emailConfirm){
            $_response['signup']['validate']['failed']['email'] = 'Emails não conferem.';
          }
          if(!isset($_response['signup']['validate']['failed'])){
            $dataset = array(
              'riot_id' => $summoner->$username->id,
              'riot_level'	=>$summoner->$username->summonerLevel,
              'name' => $summoner->$username->name,
              'username' => removeAccents($username),
              'password' => md5($password),
              'server' => $server,
              'email' => $email,
              'sex' => $sex,
            );
            $mysql->Insert($dataset,'user');
            $user->authenticate(removeAccents($username),$password);
            $_response['signup']['validate']['success'] = removeAccents($username);
          }
        }
      break;//switch($_GET['subject'])
    case 'own-all-champions':
      $user = new User($user_id);
      foreach ($champions as $champion){
        $user->addChampion($champion->id);
      }
      break;
    case 'not-own-all-champions':
      $user = new User($user_id);
      $user->removeAllChampion();
      break;
    case 'own-champion':
      $user = new User($user_id);
      $user->addChampion($champion_id);
      break;
    case 'not-own-champion':
      $user = new User($user_id);
      $user->removeChampion($champion_id);
      break;
    case 'own-skinchampion':
      $user = new User($user_id);
      $user->addChampionSkin($skin_id);
      break;
    case 'not-own-skinchampion':
      $user = new User($user_id);
      $user->removeChampionSkin($skin_id);
      break;
    case 'mail':
      extract($_POST);
      $contactName    = ucfirst(strtolower($contactName));
      $contactEmail   = strtolower($contactEmail);
      $contactMessage = strip_tags($contactMessage);
      // Validating
      if((empty($contactName))||($contactName=='Nome')||($contactName=='Name')){
        $_response['errors'][] = 'name';
      }
      if(!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)){
        $_response['errors'][] = 'email';
      }
      if((empty($contactMessage))||($contactMessage=='Mensagem')||($contactMessage=='Message')){
        $_response['errors'][] = 'message';
      }

      $addressee ='chroda@chroda.com.br';
      $subject ='Mail from ChrodaAdventures';
      $body = '<html><head><title>Mail from ChrodaAdventures</title></head><body><fieldset><legend align="center"><strong>'.$contactName.'</strong><br/><small>'.$contactEmail.'</small></legend><p>'.$contactMessage.'</p></fieldset></body></html>';

      $headers   = array();
      $headers[] = "MIME-Version: 1.0";
      $headers[] = "Content-type: text/html; charset=utf-8";
      $headers[] = "From: Contact made by $contactName <$contactEmail>";
      $headers[] = "Reply-To: Christian Marcell \"Chroda\" <$addressee>";
      $headers[] = "Subject: {$subject}";
      $headers[] = "X-Mailer: PHP/".phpversion();

      if(empty($_response['errors'])){
        mail($addressee,$subject,$body,implode("\r\n", $headers));
      }
      break;
  endswitch;
}
print json_encode( $_response );
exit(0);
?>
