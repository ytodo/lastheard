<html>
<body>
<h2>受信パケットの受信間隔表示</h2>
<h3>My:JE3HCZ   My2:7100</h3>
<?php
$data[0] = array ('', 31837, 11837, 31837);
$data[1] = array ('', 39, 11837, 11876);
$data[2] = array ('', 8, 11837, -8116);
$data[3] = array ('', 7, 11837, -28109);
$data[4] = array ('', 6, 11837, -48103);
$data[5] = array ('', 7, 11837, -68096);
$data[6] = array ('', 6, 11837, -88090);
$data[7] = array ('', 15930, 11837, -92160);
$data[8] = array ('', 31972, 11837, -80188);
$data[9] = array ('', 19993, 11837, -80195);
$data[10] = array ('', 15995, 11837, -84200);
$data[11] = array ('', 16034, 11837, -88166);
$data[12] = array ('', 36040, 11837, -72126);
$data[13] = array ('', 16018, 11837, -76108);
$data[14] = array ('', 15945, 11837, -80163);
$data[15] = array ('', 16034, 11837, -84129);
$data[16] = array ('', 16102, 11837, -88027);
$data[17] = array ('', 31894, 11837, -76133);
$data[18] = array ('', 16099, 11837, -80034);
$data[19] = array ('', 15965, 11837, -84069);
$data[20] = array ('', 15973, 11837, -88096);
$data[21] = array ('', 15988, 11837, -92108);
$data[22] = array ('', 31992, 11837, -80116);
$data[23] = array ('', 16036, 11837, -84080);
$data[24] = array ('', 15974, 11837, -88106);
$data[25] = array ('', 16764, 11837, -91342);
$data[26] = array ('', 31180, 11837, -80162);
$data[27] = array ('', 16030, 11837, -84132);
$data[28] = array ('', 16355, 11837, -87777);
$data[29] = array ('', 31795, 11837, -75982);
$data[30] = array ('', 15910, 11837, -80072);
$data[31] = array ('', 16020, 11837, -84052);
$data[32] = array ('', 15963, 11837, -88089);
$data[33] = array ('', 16015, 11837, -92074);
$data[34] = array ('', 31972, 11837, -80102);
$data[35] = array ('', 16065, 11837, -84037);
$data[36] = array ('', 16002, 11837, -88035);
$data[37] = array ('', 16001, 11837, -92034);
$data[38] = array ('', 31931, 11837, -80103);
$data[39] = array ('', 16088, 11837, -84015);
$data[40] = array ('', 15954, 11837, -88061);
$data[41] = array ('', 31982, 11837, -76079);
$data[42] = array ('', 15974, 11837, -80105);
$data[43] = array ('', 16056, 11837, -84049);
$data[44] = array ('', 15985, 11837, -88064);
$data[45] = array ('', 31975, 11837, -76089);
$data[46] = array ('', 15998, 11837, -80091);
$data[47] = array ('', 16034, 11837, -84057);
$data[48] = array ('', 16006, 11837, -88051);
$data[49] = array ('', 15999, 11837, -92052);
$data[50] = array ('', 36018, 11837, -76034);
$data[51] = array ('', 15941, 11837, -80093);
$data[52] = array ('', 16094, 11837, -83999);
$data[53] = array ('', 15958, 11837, -88041);
$data[54] = array ('', 15940, 11837, -92101);
$data[55] = array ('', 31999, 11837, -80102);
$data[56] = array ('', 15976, 11837, -84126);
$data[57] = array ('', 16117, 11837, -88009);
$data[58] = array ('', 31966, 11837, -76043);
$data[59] = array ('', 15981, 11837, -80062);
$data[60] = array ('', 15987, 11837, -84075);
$data[61] = array ('', 16046, 11837, -88029);
$data[62] = array ('', 17140, 11837, -90889);
$data[63] = array ('', 30789, 11837, -80100);
$data[64] = array ('', 16083, 11837, -84017);
$data[65] = array ('', 16012, 11837, -88005);
$data[66] = array ('', 15899, 11837, -92106);
$data[67] = array ('', 20814, 11837, -91292);
$data[68] = array ('', 31194, 11837, -80098);
$data[69] = array ('', 16071, 11837, -84027);
$data[70] = array ('', 16012, 11837, -88015);
$data[71] = array ('', 31932, 11837, -76083);
$data[72] = array ('', 16019, 11837, -80064);
$data[73] = array ('', 16030, 11837, -84034);
$data[74] = array ('', 15988, 11837, -88046);
$data[75] = array ('', 16835, 11837, -91211);
$data[76] = array ('', 19115, 11837, -92096);
$data[77] = array ('', 32018, 11837, -80078);
$data[78] = array ('', 15993, 11837, -84085);
$data[79] = array ('', 16101, 11837, -87984);
$data[80] = array ('', 16789, 11837, -91195);
$data[81] = array ('', 31167, 11837, -80028);
$img_file = "JE3HCZ___252198.png";
require_once("../phplot/phplot.php");
$graph = new PHPlot(1000,500,$img_file);
$graph->SetIsInline(TRUE);
$graph->SetDataType("text-data");
$graph->SetDataColors(array('blue', 'green', 'red'));
$graph->SetDataValues($data);
$graph->SetYTitle('uSec');
$graph->SetPlotType('lines');
$graph->SetLegend(array('Packet interval time', 'Accumulation max time', 'Accumulation  time'));
$graph->SetXLabelAngle(90);
$y_min = -92160;
$y_max = 36040;
$graph->SetLineWidths('2');
$rows = sizeof($data);
$graph->SetPlotAreaWorld(0,$y_min,$rows,$y_max);
$graph->DrawGraph();
$graph->PrintImage();
?>
<img src="JE3HCZ___252198.png">
<br>受信パケット数 82 (from JE3HCZ  )
<br>平均パケット間隔 18.781 ミリ秒 最大 36.040ミリ秒 最小 0.006ミリ秒
<br>パケット間隔の累積最大値 11.837 ミリ秒

</body>
</html>
