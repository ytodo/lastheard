<?php
require_once 'phplot.php'; // PHPlotを読み込む

// データの準備
$data = array(
  array('Jan', 40),
  array('Feb', 50),
  array('Mar', 30),
  array('Apr', 60),
  array('May', 80)
);

// PHPlotのインスタンスを作成（画像サイズ指定）
$plot = new PHPlot(600, 400);

// グラフの種類を設定（折れ線グラフ）
$plot->SetPlotType('lines');

// データを設定
$plot->SetDataValues($data);

// 軸ラベルを設定
$plot->SetTitle('Monthly Sales');
$plot->SetXTitle('Month');
$plot->SetYTitle('Sales');

// 画像を出力
$plot->DrawGraph();
?>
