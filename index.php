<?php
    // フォームから送られた内容の処理・保存

    $match_code = @$_POST['one-match-code'];

    if($match_code==""){
      $match_code = substr(mt_rand(100000, 199999), 1, 5); // 発行するコードを5桁に統一するために、6桁の乱数から5文字を取得する
      copy('./map.jpg', './tmp/'.$match_code . '.jpg'); // 新規コード発行時、地図をHerokuの一時フォルダにコピー
    }else{
      if(!$_POST["hiddenmap"]){
        // Canvasの内容がフォームから送られていない場合は何もしない
      }else{
        $map_data = $_POST["hiddenmap"]; // ユーザーが書き込んだ地図のデータ
        $map_data = base64_decode($map_data);
        file_put_contents("./tmp/".$match_code.".jpg", $map_data); // 書き込み済みの地図をHerokuの一時フォルダに保存
      }
    }
 ?>
<html>
<head>
  <meta name="robots" content="noindex"> <!-- 趣味と勉強を兼ねて作ったページのため、検索にヒットしないように -->
  <title>Apex マップメモ</title>
  <style>
    div{
      display: table;
      margin-left: auto;
      margin-right: auto;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="main">
    <div class="menu">
      <a href="javascript:void(0);" onclick="marker(1);">ドロップシップ航路</a>　
      <a href="javascript:void(0);" onclick="marker(2);">敵プレイヤーピン</a>　
    </div>
    <div class="code-form">
      <form name="form" action="index.php" method="post">
        <input type="text" name="one-match-code" placeholder="ここにマッチコードを入力" autocomplete="off" value="<?php echo $match_code; ?>">
        <input type="hidden" name="hiddenmap" value="">
        <input type="submit" value="コードから読み込む">
        <input type="submit" value="コードに保存">
        <input type="button" value="新規コード発行" onclick="location.replace('index.php');">
        <br>保存したコードは別端末・パーティーメンバーと共有できます
      </form>
    </div>

    <canvas id="map" width="850" height="850"></canvas>

    <div class="howto">
      <hr>
      使い方: １．「ドロップシップ航路」を押して始点・終点をクリック → ２．「敵プレイヤーピン」を押して他のプレイヤーがドロップシップから降下した位置をマークして保存
      <hr>
    </div>
  </div>

<script>
    const canvas = document.getElementById("map");
    const context = canvas.getContext("2d");
    const mapimg = new Image();
    const match_code = "./tmp/<?php echo $match_code; ?>" + ".jpg"; // 一時フォルダから発行したコードのデータを取得
    mapimg.src = match_code;
    mapimg.onload = function(){
      context.drawImage(mapimg, 0, 0, 850, 850);
    }

    let mode = 0; // ピン・航路マーカーのモード
    const marker = (markermode) =>{
      mode = markermode;
      if(markermode==1){
        document.body.style.cursor = "crosshair"; // 航路マーカーモードであることを表す
      }
    }

    let x, y = 0;
    let onClick = (e) =>{
      let rect = e.target.getBoundingClientRect();
      x = e.clientX - rect.left; // クリックしたウィンドウ上の座標 - Canvasの座標
      y = e.clientY - rect.top;

      if(mode==1){
        dropshipLiner();
      }else if(mode==2){
        enemyPin();
      }

    }
    let liner = 0; // 始点か終点か
    let sX, sY, eX, eY;
    const dropshipLiner = () =>{
      if(liner==0){ // 始点
        sX = x;
        sY = y;
        liner = 1; // 航路の始点記録から終点記録に切り替え
      }else if(liner==1){ // 終点
        eX = x;
        eY = y;
        context.beginPath();
        context.moveTo(sX, sY);
        context.lineTo(eX, eY);
        context.strokeStyle = "#fb1";
        context.lineWidth = 7;
        context.stroke();
        document.body.style.cursor = "auto";
        Save();
        liner = 0; // 終点モード終わり
        mode = 0; // マーカー設定を初期状態に戻す
      }
    }

    const enemyPin = () =>{
      context.fillStyle = "#f00"; // 他プレイヤー（敵）のピンは赤
      context.fillRect(x, y, 15, 15);
      Save();
    }

    const Save = () =>{ // 保存するための準備としてフォームにCanvasの内容を入れておく
      let map_data = canvas.toDataURL("image/jpg");
      map_data = map_data.replace(/^.*,/, '');
      let form = document.form;
      form.hiddenmap.value = map_data;
    }
    canvas.addEventListener('click', onClick, false);
</script>
</body>
</html>
