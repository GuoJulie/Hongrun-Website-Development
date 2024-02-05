<?php
set_time_limit(0);
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC."/oxwindow.class.php");
require_once(DEDEINC."/channelunit.class.php");
require_once(DEDEINC."/baidusitemap.func.php");
require_once(DEDEINC."/baiduxml.class.php");

if(empty($dopost)) $dopost = '';
if(empty($action)) $action = '';
if(empty($sign)) $sign = '';
if(empty($type)) $type = 1;

check_installed();

$version = baidu_get_setting('version');
if (empty($version)) $version = '0.0.2';

if (version_compare($version, PLUS_BAIDUSITEMAP_VER, '<')) {
    $mysql_version = $dsql->GetVersion(TRUE);
    
    foreach ($update_sqls as $ver => $sqls) {
        if (version_compare($ver, $version,'<')) {
            continue;
        }
        foreach ($sqls as $sql) {
            $sql = preg_replace("#ENGINE=MyISAM#i", 'TYPE=MyISAM', $sql);
            $sql41tmp = 'ENGINE=MyISAM DEFAULT CHARSET='.$cfg_db_language;
            
            if($mysql_version >= 4.1)
            {
                $sql = preg_replace("#TYPE=MyISAM#i", $sql41tmp, $sql);
            }
            $dsql->ExecuteNoneQuery($sql);
        }
        baidu_set_setting('version', $ver);
        $version=baidu_get_setting('version');
    }
}

if($dopost=='auth'){
    if ( empty($sign) )
    {
	    $siteurl=$cfg_basehost;
        $sigurl="http://baidu.api.dedecms.com/index.php?siteurl=".urlencode($siteurl);
        $result = baidu_http_send($sigurl);
    	//var_dump($result);exit();
        $data = json_decode($result, true); 
        baidu_set_setting('siteurl', $data['siteurl']);
        baidu_set_setting('checksign', $data['checksign']);
        if($data['status']==0){
            $checkurl=$siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=checkurl&checksign=".$data['checksign'];

            $authurl="http://zz.baidu.com/api/opensitemap/auth?siteurl=".$data ['siteurl']."&checkurl=".urlencode($checkurl)."&checksign=".$data['checksign'];
            $authdata = baidu_http_send($authurl);
            $output = json_decode($authdata, true);
            if($output['status']==0){
                baidu_set_setting('pingtoken', $output['token']);
                $sign = md5($data['siteurl'].$output['token']);
                ShowMsg('�ɹ�ͬ�ٶ�վ��API���ͨ�ţ�������������ύ����','?dopost=auth&sign='.$sign.'&action='.$action);
            } else {
                ShowMsg("�ύ�ٶ�����ʧ�ܣ��޷�У�鱾����Կ��Զ�̽ӿڷ������޷�������ȡ������վ���ļ��� <a href='http://www.dedecms.com/addons/baidusitemap/#help' target='_blank'>�����ȡ�������</a>","javascript:;");
                exit();
            }
        }
    } else {
        $siteurl = baidu_get_setting('siteurl');
        $type=1;
        $old_bdpwd = baidu_get_setting('bdpwd');
        if($action=='resubmit')
        {
            baidu_delsitemap($siteurl,1,$sign);
            baidu_set_setting('setupmaxaid',0);
            baidu_set_setting('bdpwd','');
            $old_bdpwd='';
        }
        
        if(empty($old_bdpwd))
        {
            $bdpwd = baidu_gen_sitemap_passwd();
            baidu_set_setting('bdpwd', $bdpwd);
            $sign = md5($siteurl.$output['token']);
            //�ύȫ������
            $type=1;
            $allreturnjson = baidu_savesitemap('save',$siteurl, 1, $bdpwd, $sign);
            $allresult = json_decode($allreturnjson['json'], true);
            baidu_set_setting('lastuptime_all', time());
        } else {
            //�ύ��������
            $type=2;
            $sign = md5($siteurl.$output['token']);
            baidu_delsitemap($siteurl,2,$sign);
            $row = $dsql->GetOne("SELECT count(*) as dd FROM `#@__plus_baidusitemap_list` where type=2");
            
            $allreturnjson = baidu_savesitemap('save',$siteurl, 2, $old_bdpwd, $sign);
            $allresult = json_decode($allreturnjson['json'], true);
            baidu_set_setting('lastuptime_inc', time());
        }
        if(0==$allresult['status'])
        {
            ShowMsg("�ٶ�վ������������ɣ������ύҳ��������ύ����","?dopost=submit&type=".$type);
            exit();
        } else {
            ShowMsg("�ύ�ٶ�����ʧ��","?");
            exit();
        }
        
    }
} elseif ( $dopost=='submit' )
{
    $bdpwd = baidu_get_setting('bdpwd');
    if(empty($bdpwd))
    {
        $bdpwd = baidu_gen_sitemap_passwd();
        baidu_set_setting('bdpwd', $bdpwd);
    }

    $siteurl = baidu_get_setting('siteurl');
    $token = baidu_get_setting('pingtoken');
    $sign=md5($siteurl.$token);
    $bdpwd=addslashes($bdpwd);
    if (1 == $type) {
        $script = 'indexall';
        $stype = 'all';
    } else if (2 == $type) {
        $script = 'indexinc';
        $stype = 'inc';
    }
    $indexurl = $siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=sitemap_urls&pwd={$bdpwd}&type={$type}";
    $submiturl="http://zz.baidu.com/api/opensitemap/savesitemap?siteurl=".urlencode($siteurl)."&indexurl=".urlencode($indexurl)."&tokensign=".urlencode($sign)."&type={$stype}&resource_name=CustomSearch_Normal";
    $rat = baidu_http_send($submiturl);
    $query = "UPDATE `#@__plus_baidusitemap_list` SET `isok` = '1'";
    $rs = $dsql->ExecuteNoneQuery($query);
    ShowMsg("�ɹ��ύ����������","?");
    exit();
} elseif ( $dopost=='searchbox2' || $dopost=='searchpage2' || $dopost=='income2' || $dopost=='report2')
{
    $site_id = baidu_get_setting('site_id');
    if ( empty($site_id) )
    {
        ShowMsg("��δ��վ��ID�����Ȱ��ٽ��в�������","?dopost=bind_site_id");
        exit();
    }
    $arr['searchbox2']['title']="���������";
    $arr['searchbox2']['url']="http://zn.baidu.com/cse/searchbox2/index?sid={$site_id}";
    $arr['searchpage2']['title']="���ҳ����";
    $arr['searchpage2']['url']="http://zn.baidu.com/cse/searchpage2/index?sid={$site_id}";
    $arr['income2']['title']="�������";
    $arr['income2']['url']="http://zn.baidu.com/cse/income2/index?sid={$site_id}";
    $arr['report2']['title']="���ݱ���";
    $arr['report2']['url']="http://zn.baidu.com/cse/report2/index?sid={$site_id}";
    if ( !isset($arr[$dopost]) )
    {
        exit('error!');
    }
    $str = <<<EOT
<script type="text/javascript" src="http://baidu.api.dedecms.com/assets/js/jquery.min.js"></script>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>�ٶ�վ������</title>
		<link rel="stylesheet" type="text/css" href="css/base.css">
	</head>
	<body background='images/allbg.gif' leftmargin="8" topmargin='8'>
		<table width="98%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#DFF9AA" height="100%">
			<tr>
				<td height="28" style="border:1px solid #DADADA" background='images/wbg.gif'>
					<div style="float:left">&nbsp;<b>��<a href="?">��ٶ�վ������ ���ṹ�������ύ::��������</b>
					</div>
					<div style="float:right;margin-right:20px;">
					</div>
				</td>
			</tr>
			<tr>
				<td width="100%" height="100%" valign="top" bgcolor='#ffffff' style="padding-top:5px">
					<table width='100%' border='0' cellpadding='3' cellspacing='1' bgcolor='#DADADA' height="100%">
						<tr bgcolor='#DADADA'>
							<td colspan='2' background='images/wbg.gif' height='26'><font color='#666600'><b>{$arr[$dopost]['title']}</b></font>
							</td>
						</tr>
						<tr bgcolor='#FFFFFF'>
							<td colspan='2' height='100%' style='padding:20px'>
								<br/>
								<iframe src="{$arr[$dopost]['url']}" scrolling="auto" width="100%" height="100%" style="border:none"></iframe>
							</td>
						</tr>
						<tr>
							<td bgcolor='#F5F5F5'>&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<p align="center">
			<br>
			<br>
		</p>
	</body>

</html>
EOT;
    echo $str;exit;
} elseif($dopost=='viewsub')
{
    $query="SELECT * FROM `#@__plus_baidusitemap_list` ORDER BY sid DESC";
    $dsql->SetQuery($query);
    $dsql->Execute('dd');
    $liststr="";
    while($arr=$dsql->GetArray('dd'))
    {
        $typestr=$arr['type']==1?'[ȫ��]':'[����]';
        $arr['isok'] = $arr['isok']==0? '<font color="red">δ�ύ</font>' : '<font color="green">���ύ</font>';
        $arr['create_time'] = Mydate('Y-m-d H:m:i',$arr['create_time']);
        $liststr.=<<<EOT
<tr align="center" bgcolor="#FFFFFF" height="26" onmousemove="javascript:this.bgColor='#FCFDEE';" onmouseout="javascript:this.bgColor='#FFFFFF';">
			<td>{$typestr}
			</td>
			<td><a href="{$arr['url']}" target="_blank">{$arr['url']}</a>
			</td>
			<td>{$arr['create_time']}</td>
			</td>
		</tr>
EOT;
    }
    //���سɹ���Ϣ
    $msg = <<<EOT
<table width="98%" border="0" align="center" cellpadding="3" cellspacing="1" bgcolor="#D6D6D6">
	<tbody>
		<tr align="center" bgcolor="#FBFCE2" height="26">
			<td width="8%">����</td>
			<td width="30%">��ַ</td>
			<td width="15%">�ύʱ��</td>
		</tr>
		 {$liststr}
		
		<tr bgcolor="#ffffff" height="28">
			<td colspan="5">�� 
			</td>
		</tr>

	</tbody>
</table>
   
EOT;
    $msg = "<div style=\"line-height:20px;\">    {$msg}</div><script type=\"text/javascript\">
function isGoUrl(url,msg)
{
	if(confirm(msg))
	{
		window.location.href=url;
	} else {
		return false;
	}
}
</script>";

    $wintitle = '�����б����';
    $wecome_info = '<a href=\'baidusitemap_main.php\'>�ٶ�վ������</a> ���ṹ�������ύ::��������';
    $win = new OxWindow();
    $win->AddTitle($wintitle);
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow('hand', '&nbsp;', false);
    $win->Display();
} elseif ( $dopost=='bind_site_id' )
{
	$siteurl=$cfg_basehost;
    $sigurl="http://baidu.api.dedecms.com/index.php?siteurl=".urlencode($siteurl);
    $result = baidu_http_send($sigurl);
	//var_dump($result);exit();
    $data = json_decode($result, true); 
    baidu_set_setting('siteurl', $data['siteurl']);
    baidu_set_setting('checksign', $data['checksign']);
    if($data['status']==0){
        $checkurl=$siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=checkurl&checksign=".$data['checksign'];

        $authurl="http://zz.baidu.com/api/opensitemap/auth?siteurl=".$data ['siteurl']."&checkurl=".urlencode($checkurl)."&checksign=".$data['checksign'];
        $authdata = baidu_http_send($authurl);
        $output = json_decode($authdata, true);
        if($output['status']==0){
            baidu_set_setting('pingtoken', $output['token']);
            $sign = md5($data['siteurl'].$output['token']);
            //$site=$siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=site_id&checksign=".$data['checksign'];
            $u = "http://zhanzhang.baidu.com/api/cooperation/cse?tokensign={$sign}&site={$data['siteurl']}";
            $login_url='https://passport.baidu.com/v2/?login&tpl=zhanzhang&u='.urlencode($u);
            //echo $login_url;exit;
            header('Location:'.$login_url);
            exit;
        } else {
            ShowMsg("�޷�У�鱾����Կ��Զ�̽ӿڷ������޷�������ȡ������վ���ļ��� <a href='http://www.dedecms.com/addons/baidusitemap/#help' target='_blank'>�����ȡ�������</a>","javascript:;");
            exit();
        }
    }
} elseif ( $dopost=='ping1' )
{
    $sigurl="http://baidu.api.dedecms.com/index.php";
    $authdata = baidu_http_send($sigurl);
    $output = json_decode($authdata, true);
    if ( $output['status']==1 )
    {
        ShowMsg("ͨ��������",-1);
        exit();
    } else {
        ShowMsg("�޷����ӣ����ķ������޷���������'http://baidu.api.dedecms.com'����ȷ������������֧��Զ�̻�ȡ�ļ���<a href='http://www.dedecms.com/addons/baidusitemap/#help' target='_blank'>�����ȡ�������</a>",'javascript:;');
        exit();
    }
} elseif ( $dopost=='ping2' )
{
    $sigurl="http://zhanzhang.baidu.com/api/opensitemap/deletesitemap";
    $authdata = baidu_http_send($sigurl);
    //$output = json_decode($authdata, true);
    if ( $output['status']==1 )
    {
        ShowMsg("ͨ��������",-1);
        exit();
    } else {
        ShowMsg("�޷����ӣ����ķ������޷���������'http://zhanzhang.baidu.com/api'����ȷ������������֧��Զ�̻�ȡ�ļ���<a href='http://www.dedecms.com/addons/baidusitemap/#help' target='_blank'>�����ȡ�������</a>",'javascript:;');
        exit();
    }
} elseif ( $dopost=='bind' )
{
    $site_id = baidu_get_setting('site_id');
    if ( !empty($site_id) )
    {
        ShowMsg("��ǰվ���Ѿ���site_id�������ظ���",-1);
        exit();
    }
    $site_id_msg = '<font color="red">��δ��վ��ID������</font><a href="?dopost=bind_site_id" style="color:blue">[��վ��ID]</a><font color="red">��ɰ�</font>';
    $siteurl = baidu_get_setting('siteurl');
    $ver = PLUS_BAIDUSITEMAP_VER;
    $siteurl2 = urlencode($siteurl);
    $msg = <<<EOT
<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#DADADA">
	<tbody>
		<tr bgcolor="#FFFFFF">
			<td colspan="2" height="100">
				<table width="98%" border="0" cellspacing="1" cellpadding="1">
					<tbody>
						<tr>
							<td width="16%" height="30">ģ��汾��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'><iframe name='stafrm' src='http://baidu.api.dedecms.com/index.php?c=welcome&m=new_ver&ver={$ver}&siteurl={$siteurl2}&setupmaxaid={$setupmaxaid}' frameborder='0' id='stafrm' width='98%' height='22'></iframe></span>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">վ���ַ��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'>{$siteurl}{$site_id_msg}</span>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">֯�νӿڵ�ַ��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'>http://baidu.api.dedecms.com</span> <a href='?dopost=ping1' style='color:blue'><u>[���ͨ��]</u></a>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">�ٶȽӿڵ�ַ��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'>http://zhanzhang.baidu.com/api/</span> <a href='?dopost=ping2' style='color:blue'><u>[���ͨ��]</u>
							</td>
						</tr>

		</tr>

		<tr>
			<td height="30" colspan="2" style="color:#999"><strong>�ٶ�վ������</strong>�ٶ�վ������ּ�ڰ���վ���ͳɱ���Ϊ��վ�û��ṩ����������վ����������ʹ�ðٶ�վ���������ߣ����������ɴ�����վר�����������棬�Զ�����Ի���չ����ʽ������ģ��ȣ���ͨ��������������롣</td>
		</tr>
		</tbody>
		</table>
		</td>
		</tr>
		<tr>
			<td bgcolor="#F5F5F5">&nbsp;</td>
		</tr>
	</tbody>
</table>
EOT;
    $msg = "<div style=\"line-height:36px;\">{$msg}</div><script type=\"text/javascript\">
function isGoUrl(url,msg)
{
	if(confirm(msg))
	{
		window.location.href=url;
	} else {
		return false;
	}
}
</script>";

    $wintitle = '�ٶ�վ������';
    $wecome_info = '�ٶ�վ������ ��';
    $win = new OxWindow();
    $win->AddTitle($wintitle);
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow('hand', '&nbsp;', false);
    $win->Display();
}

 else {
    //���سɹ���Ϣ
    $siteurl = baidu_get_setting('siteurl');
    $setupmaxaid = baidu_get_setting('setupmaxaid');
    $lastuptime_all = date('Y-m-d',baidu_get_setting('lastuptime_all'));
    $lastuptime_inc = date('Y-m-d',baidu_get_setting('lastuptime_inc'));
    $site_id = baidu_get_setting('site_id');
    if ( empty($site_id) )
    {
        header('location:?dopost=bind');
        exit;
    }
    $site_id_msg=$submitall_msg='';
    if ( empty($site_id) )
    {
        $site_id_msg = '<font color="red">��δ��վ��ID������</font><a href="?dopost=bind_site_id" style="color:blue">[��վ��ID]</a><font color="red">��ɰ�</font>';
    }
    if ( !empty($site_id) AND empty($lastuptime_all) )
    {
        //header('location:?dopost=auth&action=resubmit');
        //exit;
        $submitall_msg = '<font color="red">��δ�ύȫ�����������</font><a href="?dopost=auth&action=resubmit" style="color:blue">[�ύȫ������]</a><font color="red">�����ύ���ύ5��Сʱ������������</font>';
    }
    
    $bdarcs = new BaiduArticleXml;
    $bdarcs->setSitemapType(1);
    $maxaid = $bdarcs->getMaxAid();
    $ver = PLUS_BAIDUSITEMAP_VER;
    $siteurl2 = urlencode($siteurl);
    $msg = <<<EOT
<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#DADADA">
	<tbody>
		<tr bgcolor="#FFFFFF">
			<td colspan="2" height="100">
				<table width="98%" border="0" cellspacing="1" cellpadding="1">
					<tbody>
						<tr>
							<td width="16%" height="30">ģ��汾��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'><iframe name='stafrm' src='http://baidu.api.dedecms.com/index.php?c=welcome&m=new_ver&ver={$ver}&siteurl={$siteurl2}&setupmaxaid={$setupmaxaid}' frameborder='0' id='stafrm' width='98%' height='22'></iframe></span>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">վ���ַ��</td>
							<td width="84%" style="text-align:left;"><span style='color:black'>{$siteurl}</span>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">��վ��ID��</td>
							<td width="84%" style="text-align:left;">	<span style='color:black'>{$site_id}</span>{$site_id_msg}
								<br />
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">����ύ�ĵ�ID��</td>
							<td width="84%" style="text-align:left;">	<span style='color:black'>{$setupmaxaid} {$submitall_msg}</span>
							</td>
						</tr>
						<tr>
							<td width="16%" height="30">��ǰ�ĵ�����ID��</td>
							<td width="84%" style="text-align:left;">	<span style='color:black'>{$maxaid}</span>
							</td>
						</tr>
		</tr>
		<tr>
			<td width="16%" height="30">������������ύ��</td>
			<td width="84%" style="text-align:left;">	<span style='color:black'>{$lastuptime_inc}</span>
			</td>
		</tr>
		</tr>
		<tr>
			<td width="16%" height="30">ȫ����������ύ��</td>
			<td width="84%" style="text-align:left;">	<span style='color:black'>{$lastuptime_all}</span>
			</td>
		</tr>
		<tr>
			<td height="30" colspan="2"><b>�����Խ������²�����</b></td>
		</tr>
		<tr>
			<td height="30" colspan="2"> <a href='javascript:isGoUrl("baidusitemap_main.php?dopost=auth","�Ƿ�ȷ���ύ����������");' style='color:blue'><u>[�ύ��������]</u></a>
 <a href='javascript:isGoUrl("baidusitemap_main.php?dopost=auth&action=resubmit","�Ƿ�ȷ�������ύȫ��������");' style='color:blue'><u>[�����ύȫ������]</u></a>
 <a href='baidusitemap_main.php?dopost=searchbox2' style='color:blue'><u>[���������]</u></a>
 <a href='baidusitemap_main.php?dopost=searchpage2' style='color:blue'><u>[���ҳ����]</u></a>
 <a href='baidusitemap_main.php?dopost=income2' style='color:blue'><u>[�������]</u></a>
					<a
					href='baidusitemap_main.php?dopost=report2' style='color:blue'><u>[���ݱ���]</u>
						</a>
			</td>
		</tr>
		<tr>
			<td height="30" colspan="2">
				<hr>����˵����
				<br>�ڶ�Ӧģ����ʹ�ñ�ǩ��<font color="red">{dede:baidusitemap/}</font>��ֱ�ӽ��е��ü��ɣ���ʽ�趨�ɵ��<a href="baidusitemap_main.php?dopost=searchbox2" style="color:blue">[���������]</a> �������á�
				<hr>����˵����
				<br> <b>[�ύ��������]</b>�����ύ����Ƶ�ʽ�Ƶ����������һ����ȫ�������ύ��ɺ�ÿ�θ����������ݺ�������������ύ��
				<br> <b>[�����ύȫ������]</b>���¶�ȫվ�İٶ����������ύ��
				<br> <b>[���������]</b>�����������ģ����ʽ��
				<br> <b>[���ҳ����]</b>�������ڡ����ҳ����ҳ�棬���������ҳ�Ķ�����Ƶ������ʽģ�塢ɸѡ����ȹ��ܽ������ã�
				<br> <b>[�������]</b>ͨ����վ��������ٶ������˻�������������л����ù�����룻
				<br> <b>[���ݱ���]</b>�鿴վ����������ͳ�Ʊ���
				<br>
				<br>
				<hr>
			</td>
		</tr>
		<tr>
			<td height="30" colspan="2" style="color:#999"><strong>�ٶ�վ������</strong>�ٶ�վ������ּ�ڰ���վ���ͳɱ���Ϊ��վ�û��ṩ����������վ����������ʹ�ðٶ�վ���������ߣ����������ɴ�����վר�����������棬�Զ�����Ի���չ����ʽ������ģ��ȣ���ͨ��������������롣</td>
		</tr>
		</tbody>
		</table>
		</td>
		</tr>
		<tr>
			<td bgcolor="#F5F5F5">&nbsp;</td>
		</tr>
	</tbody>
</table>
EOT;
    $msg = "<div style=\"line-height:36px;\">{$msg}</div><script type=\"text/javascript\">
function isGoUrl(url,msg)
{
	if(confirm(msg))
	{
		window.location.href=url;
	} else {
		return false;
	}
}
</script>";

    $wintitle = '�ٶ�վ������';
    $wecome_info = '�ٶ�վ������ ��';
    $win = new OxWindow();
    $win->AddTitle($wintitle);
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow('hand', '&nbsp;', false);
    $win->Display();
}

