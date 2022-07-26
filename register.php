<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>出席管理システム</title>
  <link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
  <div class=res><?php
//変数処理
$name=''; $pwd=''; $key=file_get_contents("key/savekey.txt");
$sname=''; $spwd=''; $flag='<p>入力がありません</p>';
$name = isset($_POST['n']) ?
    htmlspecialchars($_POST['n'],ENT_QUOTES,'UTF-8') : '';
$pwd = isset($_POST['p']) ?
    htmlspecialchars($_POST['p'],ENT_QUOTES,'UTF-8') : '';

//入力内容の暗号化
$sname=openssl_encrypt($name,'AES-128-ECB',$key);
$spwd=openssl_encrypt($pwd,'AES-128-ECB',$key);

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

if(empty($name)==true||empty($pwd)==true) die($flag);

//名前とパスワードでマッチング
$sql='SELECT COUNT(*) FROM emp WHERE name = :name AND pwd = :pwd';
$stmt=$conn->prepare($sql);
$stmt->bindParam(':name',$sname,PDO::PARAM_STR);
$stmt->bindParam(':pwd',$spwd,PDO::PARAM_STR);
$stmt->execute();
$result=$stmt->fetch(PDO::FETCH_COLUMN);
if(!(empty($result))){
  $sql=sprintf("SELECT COUNT(*) FROM emp WHERE name='%s'",$sname);
  $stmt=$conn->prepare($sql);
  $stmt->execute();
  $result=$stmt->fetch(PDO::FETCH_COLUMN);
  if(!(empty($result))){
    echo "<p>{$name}様は登録済みです。出席情報の記録を開始できます。</p>";
    goto regend;
  }
}
//利用者情報登録
$sql="INSERT INTO emp(name,pwd) VALUES(:sname,:spwd)";
$stmt=$conn->prepare($sql);
$stmt->bindParam(':sname',$sname,PDO::PARAM_STR);
$stmt->bindParam(':spwd',$spwd,PDO::PARAM_STR);
$stmt->execute();
$sql=sprintf("CREATE TABLE IF NOT EXISTS `%s`(ROWID int(50) UNSIGNED NOT NULL auto_increment UNIQUE, startwork varchar(256) , endwork varchar(256))",$sname);
$stmt=$conn->prepare($sql);
$stmt->execute();

echo $name."<p>様の情報を登録しました。</p>";
?>
    </table>
  </div>
</div>
<?php regend: ?>
<div class="return">
<button type="button" class="end_btn" onclick="location.href='./index.html'">戻る</button>
</div>
</body>

</html>