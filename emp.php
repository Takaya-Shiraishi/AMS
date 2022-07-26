<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>出席管理システム</title>
  <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
  <div class=res>
<?php
//変数処理
$name=''; $pwd=''; $code=''; $key=file_get_contents("key/savekey.txt"); $data=''; $result=''; $timelog='';
$sname=''; $spwd=''; $date=''; $flag='入力がありません'; $heredoc='';
$name = isset($_POST['n']) ?
htmlspecialchars($_POST['n'],ENT_QUOTES,'UTF-8') : '';
$pwd = isset($_POST['p']) ?
htmlspecialchars($_POST['p'],ENT_QUOTES,'UTF-8') : '';
$code = isset($_POST['code']) ?
htmlspecialchars($_POST['code'],ENT_QUOTES,'UTF-8') : '';

if(empty($name)==true||empty($pwd)==true||empty($code)==true) if($code!=3) die($flag);

//データベース接続・作成
$dsn=file_get_contents("key/dsn.txt");
$user=file_get_contents("key/dbms_uname.txt");
$password=file_get_contents("key/dbms_pwd.txt");
$conn = new PDO($dsn, $user, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//テーブルがなかったら作成
$sql="CREATE TABLE IF NOT EXISTS emp(name varchar(256), pwd varchar(256))";
$stmt=$conn->prepare($sql);
$stmt->execute();

//入力内容の暗号化
$sname=openssl_encrypt($name,'AES-128-ECB',$key);
$spwd=openssl_encrypt($pwd,'AES-128-ECB',$key);

if($code!=3){
//名前とパスワードでマッチング
$sql='SELECT COUNT(*) FROM emp WHERE name = :name AND pwd = :pwd';
$stmt=$conn->prepare($sql);
$stmt->bindParam(':name',$sname,PDO::PARAM_STR);
$stmt->bindParam(':pwd',$spwd,PDO::PARAM_STR);
$stmt->execute();
$result=$stmt->fetch(PDO::FETCH_COLUMN);
if(empty($result)){
  echo "<p>{$name}様の登録が確認出来ませんでした。受講者登録をしてからお試しください</p>";
  goto end;
}
}
//時刻のローカライズ
  date_default_timezone_set('Asia/Tokyo');
  //時刻の発行
  $timelog=openssl_encrypt(date("c"),'AES-128-ECB',$key);
//出勤時の処理
if($code==1){
  //SQL文の発行
  $sql=sprintf("INSERT INTO `%s`(startwork) VALUES(:timelog)",$sname);
  $stmt=$conn->prepare($sql);
  $stmt->bindParam(':timelog',$timelog,PDO::PARAM_STR);
  $stmt->execute();

  //表示
  echo "<p>出席情報を登録しました。</p>";  
  }


  //退席時の処理
  if($code==2){
    //SQL文の発行
    $sql=sprintf("SELECT MAX(ROWID) FROM `%s`",$sname);
    $stmt=$conn->prepare($sql);
    $stmt->execute();
    $result=$stmt->fetch(PDO::FETCH_COLUMN);
    if($result===false) {
      echo '<p>データベースに情報がありません</p>';
      goto end;
    }
    $timelog=openssl_encrypt(date("c"),'AES-128-ECB',$key);
    $sql=sprintf("UPDATE `%s` SET endwork=:timelog WHERE ROWID=:rows",$sname);
    $stmt=$conn->prepare($sql);
    $stmt->bindParam(':timelog',$timelog,PDO::PARAM_STR);
    $stmt->bindParam(':rows',$result,PDO::PARAM_STR);
    $stmt->execute();
  
    //表示
    echo "<p>出席情報を登録しました。お疲れさまでした。</p>";
    }
  

  //出席情報の表示
  if($code==3):
    //SQL文の発行
  $sql=sprintf("SELECT * FROM `%s`",$sname);
  $stmt=$conn->prepare($sql);
  $stmt->execute();
  //SQL文の受取
  $data=$stmt->fetchAll();
  if($data===false) {
    echo "<p>記録がありません</p>";
    goto end;
  }
  //表示する表のフォーマットを作成
printf("
  <h1>出席管理システム</h1>
  <h3>%s様の出席記録です</h3>
  <div class=\"matrix\">
  <table>
  <tr>
    <th>出席時刻</th><th>退席時刻</th>
  </tr>",$name);

  foreach($data as $result):
?>
<tr>
  <td><?php echo openssl_decrypt($result['startwork'],'AES-128-ECB',$key);?></td>
  <td><?php echo openssl_decrypt($result['endwork'],'AES-128-ECB',$key);?></td>
</tr>
<?php
endforeach;
endif;

if($code==4){
  $sql=sprintf("DELETE FROM `%s` WHERE startwork IS NULL OR endwork IS NULL",$sname);
  $stmt=$conn->prepare($sql);
  $stmt->execute();
  echo "<p>出席時刻と欠席時刻が対応していないデータを削除しました。</p>";
  goto end;
}
 ?>
 </table>
 </div>
</div>
<?php end: ?>
<div class="return">
<button type="button" class="end_btn" onclick="location.href='./index.html'">戻る</button>
</div>
</body>

</html>
