<?php
/**
 * �ĵ�����
 *
 * @version        $Id: archives_do.php 1 8:26 2010��7��12��Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEADMIN.'/inc/inc_batchup.php');
require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/arc.archives.class.php');
$ENV_GOBACK_URL = (empty($_COOKIE['ENV_GOBACK_URL']) ? 'content_list.php' : $_COOKIE['ENV_GOBACK_URL']);

if(empty($dopost))
{
    ShowMsg('�Բ�����ûָ�����в�����','-1');
    exit();
}
$aid = isset($aid) ? preg_replace("#[^0-9]#", '', $aid) : '';

/*--------------------------
//�༭�ĵ�
function editArchives(){ }
---------------------------*/
if($dopost=='editArchives')
{
    $query = "SELECT arc.id,arc.typeid,ch.maintable,ch.editcon
           FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel
          WHERE arc.id='$aid' ";
    $row = $dsql->GetOne($query);
    $gurl = $row['editcon'];
    if($gurl=='') $gurl='article_edit.php';
    header("location:{$gurl}?aid=$aid");
    exit();
}
/*--------------------------
//����ĵ�
function viewArchives(){ }
---------------------------*/
else if($dopost=="viewArchives")
{
    $aid = preg_replace("#[^0-9]#", '', $aid);

    //��ȡ������Ϣ
    $query = "SELECT arc.*,ch.maintable,ch.addtable,ch.issystem,ch.editcon,
              tp.typedir,tp.typename,tp.corank,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.sitepath,tp.siteurl
           FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch ON ch.id=tp.channeltype
           WHERE arc.id='$aid' ";
    $trow = $dsql->GetOne($query);
    $trow['maintable'] = ( trim($trow['maintable'])=='' ? '#@__archives' : trim($trow['maintable']) );
    if($trow['issystem'] != -1)
    {
        $arcQuery = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.sitepath,tp.siteurl
                   FROM `{$trow['maintable']}` arc LEFT JOIN `#@__arctype` tp on arc.typeid=tp.id
                   LEFT JOIN `#@__channeltype` ch on ch.id=arc.channel WHERE arc.id='$aid' ";
        $arcRow = $dsql->GetOne($arcQuery);
		PutCookie('DedeUserID',$arcRow['mid'],1800);
		PutCookie('DedeLoginTime',time(),1800);
        if($arcRow['ismake']==-1 || $arcRow['corank']!=0 || $arcRow['arcrank']!=0 || ($arcRow['typeid']==0 && $arcRow['channel']!=-1) || $arcRow['money']>0)
        {
            echo "<script language='javascript'>location.href='{$cfg_phpurl}/view.php?aid={$aid}';</script>";
            exit();
        }
    }
    else
    {
        $arcRow['id'] = $aid;
        $arcRow['typeid'] = $trow['typeid'];
        $arcRow['senddate'] = $trow['senddate'];
        $arcRow['title'] = '';
        $arcRow['ismake'] = 1;
        $arcRow['arcrank'] = $trow['corank'];
        $arcRow['namerule'] = $trow['namerule'];
        $arcRow['typedir'] = $trow['typedir'];
        $arcRow['money'] = 0;
        $arcRow['filename'] = '';
        $arcRow['moresite'] = $trow['moresite'];
        $arcRow['siteurl'] = $trow['siteurl'];
        $arcRow['sitepath'] = $trow['sitepath'];
    }
    $arcurl  = GetFileUrl($arcRow['id'],$arcRow['typeid'],$arcRow['senddate'],$arcRow['title'],$arcRow['ismake'],$arcRow['arcrank'],
    $arcRow['namerule'],$arcRow['typedir'],$arcRow['money'],$arcRow['filename'],$arcRow['moresite'],$arcRow['siteurl'],$arcRow['sitepath']);
    $arcfile = GetFileUrl($arcRow['id'],$arcRow['typeid'],$arcRow['senddate'],$arcRow['title'],
    $arcRow['ismake'],$arcRow['arcrank'],$arcRow['namerule'],$arcRow['typedir'],$arcRow['money'],$arcRow['filename']);
    if(preg_match("#^http:#", $arcfile))
    {
        $arcfile = preg_replace("#^http:\/\/([^\/]*)\/#i", '/', $arcfile);
    }
    $truefile = GetTruePath().$arcfile;
    if(!file_exists($truefile))
    {
        MakeArt($aid,TRUE);
    }
    echo "<script language='javascript'>location.href='$arcurl"."?".time()."';</script>";
    exit();
}
/*--------------------------
//�첽�ϴ�����ͼ
function uploadLitpic(){ }
---------------------------*/
else if($dopost=="uploadLitpic")
{
    $upfile = AdminUpload('litpic', 'imagelit', 0, false );
    if($upfile=='-1')
    {
        $msg = "<script language='javascript'>
                parent.document.getElementById('uploadwait').style.display = 'none';
                alert('��ûָ��Ҫ�ϴ����ļ����ļ���С�������ƣ�');
            </script>";
    }
    else if($upfile=='-2')
    {
        $msg = "<script language='javascript'>
                parent.document.getElementById('uploadwait').style.display = 'none';
                alert('�ϴ��ļ�ʧ�ܣ�����ԭ��');
            </script>";
    }
    else if($upfile=='0')
    {
        $msg = "<script language='javascript'>
                parent.document.getElementById('uploadwait').style.display = 'none';
                alert('�ļ����Ͳ���ȷ��');
            </script>";
    }
    else
    {
         if(!empty($cfg_uplitpic_cut) && $cfg_uplitpic_cut=='N')
         {
                 $msg = "<script language='javascript'>
                    parent.document.getElementById('uploadwait').style.display = 'none';
                    parent.document.getElementById('picname').value = '{$upfile}';
                    if(parent.document.getElementById('divpicview'))
                    {
                        parent.document.getElementById('divpicview').style.width = '150px';
                        parent.document.getElementById('divpicview').innerHTML = \"<img src='{$upfile}?n' width='150' />\";
                    }
                </script>";
         }
         else
         {
               $msg = "<script language='javascript'>
                    parent.document.getElementById('uploadwait').style.display = 'none';
                    window.open('imagecut.php?f=picname&isupload=yes&file={$upfile}', 'popUpImagesWin', 'scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=150, top=50');
                </script>";
         }
    }
    echo $msg;
    exit();
}
/*--------------------------
//�Ƽ��ĵ�
function commendArchives(){ }
---------------------------*/
else if($dopost=="commendArchives")
{
    CheckPurview('a_Commend,sys_ArcBatch');
    if( !empty($aid) && empty($qstr) )
    {
        $qstr = $aid;
    }
    if($qstr=='')
    {
        ShowMsg("������Ч��",$ENV_GOBACK_URL);
        exit();
    }
    $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
    $query = "SELECT arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp on tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch on ch.id=tp.channeltype
          WHERE arc.id in($arcids) ";
    $dsql->SetQuery($query);
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        $aid = $row['id'];
        if($row['issystem']!=-1)
        {
            $maintable = ( trim($row['maintable'])=='' ? '#@__archives' : trim($row['maintable']) );
            $arr = $dsql->GetOne("SELECT flag FROM `{$maintable}` WHERE id='$aid' ");
            $flag = ($arr['flag']=='' ? 'c' : $arr['flag'].',c');
            $dsql->ExecuteNoneQuery(" UPDATE `{$maintable}` SET `flag`='$flag' WHERE id='{$aid}' ");
        }
        else
        {
            $maintable = trim($row['addtable']);
            $arr = $dsql->GetOne("SELECT flag From `{$maintable}` where aid='$aid' ");
            $flag = ($arr['flag']=='' ? 'c' : $arr['flag'].',c');
            $dsql->ExecuteNoneQuery(" UPDATE `{$maintable}` SET `flag`='$flag' WHERE aid='{$aid}' ");
        }
    }
    ShowMsg("�ɹ�����ѡ���ĵ���Ϊ�Ƽ���",$ENV_GOBACK_URL);
    exit();
}

/*--------------------------
//����HTML
function makeArchives();
---------------------------*/
else if($dopost=="makeArchives")
{
    CheckPurview('sys_MakeHtml,sys_ArcBatch');
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;
    
    if($qstr=='')
    {
        ShowMsg('������Ч��',$ENV_GOBACK_URL);
        exit();
    }
    require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
    $qstrs = explode('`',$qstr);
    $i = 0;
    foreach($qstrs as $aid)
    {
        $i++;
        $pageurl = MakeArt($aid,false);
    }
    ShowMsg("�ɹ�����ָ�� $i ���ļ�...",$ENV_GOBACK_URL);
    exit();
}
/*--------------------------
//����ĵ�
function checkArchives() {   }
---------------------------*/
else if($dopost=="checkArchives")
{
    CheckPurview('a_Check,a_AccCheck,sys_ArcBatch');
    require_once(DEDEADMIN."/inc/inc_archives_functions.php");
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;
    if($qstr=='')
    {
        ShowMsg("������Ч��",$ENV_GOBACK_URL);
        exit();
    }
    $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
    $query = "SELECT arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable FROM `#@__arctiny` arc
               LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
            LEFT JOIN `#@__channeltype` ch ON ch.id=tp.channeltype
            WHERE arc.id in($arcids) ";
    $dsql->SetQuery($query);
    $dsql->Execute('ckall');
    while($row = $dsql->GetArray('ckall'))
    {
        $aid = $row['id'];
        $maintable = ( trim($row['maintable'])=='' ? '#@__archives' : trim($row['maintable']) );
        $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET arcrank='0' WHERE id='$aid' ");
        if($row['issystem']==-1)
        {
            $dsql->ExecuteNoneQuery("UPDATE `".trim($row['addtable'])."` SET arcrank='0' WHERE aid='$aid' ");
        }
        else
        {
            $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET arcrank='0', dutyadmin='".$cuserLogin->getUserID()."' WHERE id='$aid' ");
        }
        $dsql->ExecuteNoneQuery("UPDATE `#@__taglist` SET arcrank='0' WHERE aid='$aid' "); 
        $pageurl = MakeArt($aid,false);
    }
    ShowMsg("�ɹ����ָ�����ĵ���",$ENV_GOBACK_URL);
    exit();
}
/*--------------------------
//ɾ���ĵ�
function delArchives(){ }
---------------------------*/
else if($dopost=="delArchives")
{
    CheckPurview('a_Del,a_AccDel,a_MyDel,sys_ArcBatch');
    require_once(DEDEINC."/oxwindow.class.php");
    if(empty($fmdo)) $fmdo = '';

    if($fmdo=='yes')
    {
        if( !empty($aid) && empty($qstr) )
        {
            $qstr = $aid;
        }
        if($qstr=='')
        {
            ShowMsg("������Ч��",$ENV_GOBACK_URL);
            exit();
        }
        $qstrs = explode("`",$qstr);
        $okaids = Array();

        foreach($qstrs as $aid)
        {
            if(!isset($okaids[$aid]))
            {
                DelArc($aid);
            }
            else
            {
                $okaids[$aid] = 1;
            }
        }
        ShowMsg("�ɹ�ɾ��ָ�����ĵ���",$ENV_GOBACK_URL);
        exit();
    }

    else
    {
        $wintitle = "�ĵ�����-ɾ���ĵ�";
        $wecome_info = "<a href='".$ENV_GOBACK_URL."'>�ĵ�����</a>::ɾ���ĵ�";
        $win = new OxWindow();
        $win->Init("archives_do.php","js/blank.js","POST");
        $win->AddHidden("fmdo","yes");
        $win->AddHidden("dopost",$dopost);
        $win->AddHidden("qstr",$qstr);
        $win->AddHidden("aid",$aid);
        $win->AddTitle("��ȷʵҪɾ���� $qstr �� $aid ����Щ�ĵ���");
        $winform = $win->GetWindow("ok");
        $win->Display();
    }
}
/*-----------------------------
function moveArchives(){ }
------------------------------*/
else if($dopost=='moveArchives')
{
    CheckPurview('sys_ArcBatch');
    if(empty($totype))
    {
        require_once(DEDEINC.'/typelink.class.php');
        if( !empty($aid) && empty($qstr) ) $qstr = $aid;
 
        AjaxHead();
        $channelid = empty($channelid) ? 0 : $channelid;
        $tl = new TypeLink($aid);
        $typeOptions = $tl->GetOptionArray(0, $admin_catalogs, $channelid);
        $typeOptions = "<select name='totype' style='width:90%'>
        <option value='0'>��ѡ���ƶ�����λ��...</option>\r\n
        $typeOptions
        </select>";
        
        //���AJAX���ƶ�����
        $divname = 'moveArchives';
        echo "<div class='title' onmousemove=\"DropMoveHand('{$divname}', 225);\" onmousedown=\"DropStartHand();\" onmouseup=\"DropStopHand();\">\r\n";
        echo "    <div class='titLeft'>�ƶ��ĵ�</div>\r\n";
        echo "    <div class='titRight'><img src='images/ico-close.gif' style='cursor:pointer;' onclick='HideObj(\"{$divname}\");ChangeFullDiv(\"hide\");' alt='�ر�' title='�ر�' /></div>\r\n";
        echo "</div>\r\n";
        echo "<form name='quickeditform' action='archives_do.php' method='post'>\r\n";
        echo "<input type='hidden' name='dopost' value='{$dopost}' />\r\n";
        echo "<input type='hidden' name='qstr' value='{$qstr}' />\r\n";
        echo "<table width='100%' style='margin-top:6px;z-index:9000;'>\r\n";
?>
<tr height='28'>
    <td width="80" class='bline'>&nbsp;Ŀ����Ŀ��</td>
    <td class='bline'>
    <?php echo $typeOptions; ?>
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;�ĵ�ID��</td>
    <td class='bline'>
        <input type='text' name='tmpids' value="<?php echo $qstr; ?>" style='width:310px;overflow:hidden;' />
        <br />
        �ƶ�����Ŀ����Ŀ�����ѡ�����ĵ�Ƶ������һ�£����������Զ����Բ����ϵ��ĵ���
    </td>
</tr>
<tr height='32'>
    <td colspan='2' align='center' style='padding-top:12px'>
        <input name="imageField" type="image" src="images/button_ok.gif" width="60" height="22" class="np" border="0" style="cursor:pointer" />
        &nbsp;&nbsp;
        <img src="images/button_back.gif" width="60" height="22" border="0" onclick='HideObj("<?php echo $divname; ?>");ChangeFullDiv("hide");' style="cursor:pointer" />
    </td>
</td>
</tr>
</table>
</form>
<?php
    //AJAX�������
    }
    else
    {
        $totype = preg_replace("#[^0-9]#", '', $totype);
        $typeInfos = $dsql->GetOne("SELECT tp.channeltype,tp.ispart,tp.channeltype,ch.maintable,ch.addtable,ch.issystem FROM `#@__arctype` tp LEFT JOIN `#@__channeltype` ch on ch.id=tp.channeltype WHERE tp.id='$totype' ");
        $idtype = "id";
        if(!is_array($typeInfos))
        {
            ShowMsg('��������','-1');
            exit();
        }
        if($typeInfos['ispart']!=0)
        {
            ShowMsg('�ĵ��������Ŀ����Ϊ�����б���Ŀ��','-1');
            exit();
        }
        if(empty($typeInfos['addtable']))
        {
            $typeInfos['maintable'] = '#@__archives';
        }
        //���ӵ���ģ���ж�
        if($typeInfos['issystem'] == -1)
        {
            $typeInfos['maintable'] = $typeInfos['addtable'];
            $idtype = "aid";
        }
        $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
        $arc = '';
        $j = 0;
        $okids = array();
        $dsql->SetQuery("SELECT {$idtype},typeid FROM `{$typeInfos['maintable']}` WHERE {$idtype} in($arcids) AND channel='{$typeInfos['channeltype']}' ");
        $dsql->Execute();
        while($row = $dsql->GetArray())
        {
            if($row['typeid']!=$totype)
            {
                $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny`  SET typeid='$totype' WHERE id='{$row[$idtype]}' ");
                $dsql->ExecuteNoneQuery("UPDATE `{$typeInfos['maintable']}` SET typeid='$totype' WHERE id='{$row[$idtype]}' ");
                $dsql->ExecuteNoneQuery("UPDATE `{$typeInfos['addtable']}` SET typeid='$totype' WHERE aid='{$row[$idtype]}' ");
                $okids[] = $row[$idtype];
                $j++;
            }
        }
        //����HTML
        foreach($okids as $aid)
        {
            $arc = new Archives($aid);
            $arc->MakeHtml();
        }
        ShowMsg("�ɹ��ƶ� $j ���ĵ���", $ENV_GOBACK_URL);
        exit();
    }
}
/*-----------------------------
//��ԭ�ĵ�
function RbReturnArchives(){ }
------------------------------*/
else if($dopost=='return')
{
    CheckPurview('a_Del,a_AccDel,a_MyDel,sys_ArcBatch');
    require_once(DEDEINC."/oxwindow.class.php");

    if( !empty($aid) && empty($qstr) ) $qstr = $aid;

    if($qstr=='')
    {
        ShowMsg("������Ч��","recycling.php");
        exit();
    }
    $qstrs = explode("`", $qstr);
    foreach($qstrs as $aid)
    {
        $dsql->ExecuteNoneQuery("UPDATE `#@__archives` SET arcrank='-1',ismake='0' WHERE id='$aid'");
        $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET `arcrank` = '-1' WHERE id = '$aid'; ");
    }
    ShowMsg("�ɹ���ԭָ�����ĵ���","recycling.php");
    exit();
}
/*-----------------------------
//����ĵ�
function RbClearArchives(){ }
------------------------------*/
else if($dopost=='clear')
{
    CheckPurview('a_Del,a_AccDel,a_MyDel,sys_ArcBatch');
    require_once(DEDEINC."/oxwindow.class.php");
    if(empty($fmdo)) $fmdo = '';
    $recycle = empty($recycle)? "" : $recycle;

    if($fmdo=='yes')
    {
        if( !empty($aid) && empty($qstr) ) $qstr = $aid;
        if($qstr=='')
        {
            ShowMsg("������Ч��","recycling.php");
            exit();
        }
        $qstrs = explode(",", $qstr);
        $okaids = Array();
        foreach($qstrs as $qstr)
        {
            if(!isset($okaids[$qstr]))
            {
                DelArc($qstr, "OK", FALSE, $recycle);
                $okaids[$qstr] = $qstr;
            }
            else
            {
                $okaids[$qstr] = 1;
            }
        }
        ShowMsg("�ɹ�ɾ��ָ�����ĵ���","recycling.php");
        exit();
    }
    else
    {
        $dsql->SetQuery("SELECT id FROM `#@__archives` WHERE `arcrank` = '-2'");
        $dsql->Execute();
        $qstr = '';
        while($row = $dsql->GetArray())
        {
            $qstr .= $row['id'].",";
            $aid = $row['id'];
        }
        $num = $dsql->GetTotalRow();
        if(empty($num))
        {
            ShowMsg("�Բ���δ��������ĵ���","recycling.php");
            exit();
        }
        $wintitle = "�ĵ�����-��������ĵ�";
        $wecome_info = "<a href='recycling.php'>�ĵ�����վ</a>::��������ĵ�";
        $win = new OxWindow();
        $win->Init("archives_do.php","js/blank.js","POST");
        $win->AddHidden("fmdo","yes");
        $win->AddHidden("dopost",$dopost);
        $win->AddHidden("qstr",$qstr);
        $win->AddHidden("aid",$aid);
        $win->AddHidden("recycle",$recycle);
        $win->AddTitle("���β�������ջ���վ<font color='#FF0000'>���й� $num ƪ�ĵ�</font><br>��ȷʵҪ����ɾ���� $qstr ����Щ�ĵ���");
        $winform = $win->GetWindow("ok");
        $win->Display();
    }
}
/*-----------------------------
//����ĵ�
function RbDelArchives(){ }
------------------------------*/
else if($dopost=='del')
{
    CheckPurview('a_Del,a_AccDel,a_MyDel,sys_ArcBatch');
    require_once(DEDEINC."/oxwindow.class.php");
    if(empty($fmdo)) $fmdo = '';
    $recycle = empty($recycle)? "" : $recycle;

    if($fmdo=='yes')
    {
        if( !empty($aid) && empty($qstr) ) $qstr = $aid;
        if($qstr=='')
        {
            ShowMsg("������Ч��","recycling.php");
            exit();
        }
        $qstrs = explode("`", $qstr);
        $okaids = Array();

        foreach($qstrs as $aid)
        {
            if(!isset($okaids[$aid]))
            {
                DelArc($aid,"OK","",$recycle);
            }
            else
            {
                $okaids[$aid] = 1;
            }
        }
        ShowMsg("�ɹ�ɾ��ָ�����ĵ���","recycling.php");
        exit();
    }

    else
    {
        $wintitle = "�ĵ�����-ɾ���ĵ�";
        $wecome_info = "<a href='recycling.php'>�ĵ�����</a>::ɾ���ĵ�";
        $win = new OxWindow();
        $win->Init("archives_do.php","js/blank.js","POST");
        $win->AddHidden("fmdo","yes");
        $win->AddHidden("dopost",$dopost);
        $win->AddHidden("qstr",$qstr);
        $win->AddHidden("aid",$aid);
        $win->AddHidden("recycle",$recycle);
        $win->AddTitle("��ȷʵҪ����ɾ���� $qstr �� $aid ����Щ�ĵ���");
        $winform = $win->GetWindow("ok");
        $win->Display();
    }
}
/*-----------------------------
//���ٱ༭
function quickEdit(){ }
------------------------------*/
else if($dopost=='quickEdit')
{
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    AjaxHead();
    $query = "SELECT ch.typename as channelname,ch.addtable,ar.membername as rankname,arc.*
    FROM `#@__archives` arc
    LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel
    LEFT JOIN `#@__arcrank` ar ON ar.rank=arc.arcrank WHERE arc.id='$aid' ";
    $arcRow = $dsql->GetOne($query);
    $divname = 'quickEdit';
    echo "<div class='title' onmousemove=\"DropMoveHand('{$divname}', 225);\" onmousedown=\"DropStartHand();\" onmouseup=\"DropStopHand();\">\r\n";
    echo "    <div class='titLeft'>�������Ա༭</div>\r\n";
    echo "    <div class='titRight'><img src='images/ico-close.gif' style='cursor:pointer;' onclick='HideObj(\"{$divname}\");ChangeFullDiv(\"hide\");' alt='�ر�' title='�ر�' /></div>\r\n";
    echo "</div>\r\n";
    echo "<form name='quickeditform' action='archives_do.php?dopost=quickEditSave&aid={$aid}' method='post'>\r\n";
    echo "<input type='hidden' name='addtable' value='{$arcRow['addtable']}' />\r\n";
    echo "<input type='hidden' name='oldtypeid' value='{$arcRow['typeid']}' />\r\n";
    echo "<table width='100%' style='margin-top:6px;z-index:9000;'>\r\n";
?>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;������Ŀ��</td>
    <td class='bline'>
        <?php
            $typeOptions = GetOptionList($arcRow['typeid'],$cuserLogin->getUserChannel(), $arcRow['channel']);
            echo "<select name='typeid' style='width:70%'>\r\n";
            if($arcRow["typeid"]=="0") echo "<option value='0' selected>��ѡ����Ŀ...</option>\r\n";
            echo $typeOptions;
            echo "</select>";
        ?>
    </td>
</tr>
<tr height='28'>
    <td width="80" class='bline'>&nbsp;�� �ԣ�</td>
    <td class='bline'>
    <input type='hidden' name='oldflag' value='<?php echo $arcRow['flag']; ?>' />
    <?php
    $dsql->SetQuery("SELECT * FROM `#@__arcatt` ORDER BY sortid ASC");
    $dsql->Execute();
    while($trow = $dsql->GetObject())
    {
        if($trow->att=='j' || $trow->att=='p') continue;
                  if(preg_match("#".$trow->att."#", $arcRow['flag']))
                  echo "<input class='np' type='checkbox' name='flags[]' id='flags{$trow->att}' value='{$trow->att}' checked='checked' />{$trow->attname}.{$trow->att}";
        else
                  echo "<input class='np' type='checkbox' name='flags[]' id='flags{$trow->att}' value='{$trow->att}' />{$trow->attname}.{$trow->att}";
    }
    ?>
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;�� �⣺</td>
    <td class='bline'>
        <input name="title" type="text" id="title" value="<?php echo $arcRow['title']; ?>" style="width:90%" />
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;���Ա��⣺</td>
    <td class='bline'>
        <input name="shorttitle" type="text" id="shorttitle" value="<?php echo $arcRow['shorttitle']; ?>" style="width:60%" />
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;�Ķ�Ȩ�ޣ�</td>
    <td class='bline'>
        <select name="arcrank" id="arcrank" style="width:120px">
    <option value='<?php echo $arcRow["arcrank"]?>'>
    <?php echo $arcRow["rankname"]?>                </option>
    <?php
    $urank = $cuserLogin->getUserRank();

    $dsql->SetQuery("SELECT * FROM `#@__arcrank` WHERE adminrank<='$urank'");
    $dsql->Execute();
    while($row = $dsql->GetObject()){
        echo "     <option value='".$row->rank."'>".$row->membername."</option>\r\n";
    }
    ?>
    </select>
    ��Ҫ��ң�<input name="money" type="text" id="money" value="<?php echo $arcRow["money"]; ?>" style="width:80px" />
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;�ؼ��֣�</td>
    <td class='bline'>
        <input name="keywords" type="text" id="keywords" value="<?php echo $arcRow['keywords']; ?>" style="width:70%" />
    </td>
</tr>
<tr height='32'>
    <td colspan='2' align='center' style='padding-top:12px'>
        <input name="imageField" type="image" src="images/button_ok.gif" width="60" height="22" class="np" border="0" style="cursor:pointer" />
        &nbsp;&nbsp;
        <img src="images/button_back.gif" width="60" height="22" border="0" onclick='HideObj("<?php echo $divname; ?>");ChangeFullDiv("hide");' style="cursor:pointer" />
    </td>
</td>
</tr>
</table>
</form>
<?php
//AJAX�������
}
/*-----------------------------
//������ٱ༭������
function quickEditSave(){ }
------------------------------*/
else if($dopost=='quickEditSave')
{
    require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
    //Ȩ�޼��
    if(!TestPurview('a_Edit'))
    {
        if(TestPurview('a_AccEdit'))
        {
            CheckCatalog($typeid, "�Բ�����û�в�����Ŀ {$typeid} ���ĵ�Ȩ�ޣ�");
        }
        else
        {
            CheckArcAdmin($aid, $cuserLogin->getUserID());
        }
    }
    $title = dede_htmlspecialchars(cn_substrR($title, $cfg_title_maxlen));
    $shorttitle = cn_substrR($shorttitle, 36);
    $keywords = trim(cn_substrR($keywords, 60));
    if(!TestPurview('a_Check,a_AccCheck,a_MyCheck')) $arcrank = -1;

    $adminid = $cuserLogin->getUserID();
    
    //���Դ���
    $flag = isset($flags) ? join(',', $flags) : '';
    if(!empty($flag))
    {
        if(preg_match("#p#", $oldflag)) $flag .= ',p';
        if(preg_match("#j#", $oldflag)) $flag .= ',j';
    }
	/*
    else
    {
        $flag = $oldflag;
    }
	*/
    
    $query = "UPDATE `#@__archives` SET
    typeid = '$typeid',
    flag = '$flag',
    arcrank = '$arcrank',
    money = '$money',
    title = '$title', 
    shorttitle = '$shorttitle',
    keywords = '$keywords',
    dutyadmin = '$adminid'
    WHERE id = '$aid'; ";
    //��������
    $dsql->ExecuteNoneQuery($query);
    //����΢��
    $dsql->ExecuteNoneQuery(" UPDATE `#@__arctiny` SET typeid='$typeid',arcrank='$arcrank' WHERE id='$aid' ");
    //���¸��ӱ�
    if($typeid != $oldtypeid)
    {
        $addtable = trim($addtable);
        if(empty($addtable)) $addtable = '#@__addonarticle';
        else $addtable = preg_replace("#[^a-z0-9__#@-]#i", "", $addtable);
            $dsql->ExecuteNoneQuery(" UPDATE `$addtable` SET typeid='$typeid' WHERE aid='$aid' ");
      }
    //����HTML
    $artUrl = MakeArt($aid, TRUE, TRUE);

        $backurl = !empty($_COOKIE['ENV_GOBACK_URL']) ? $_COOKIE['ENV_GOBACK_URL'] : '-1';
        ShowMsg('�ɹ�����һƪ�ĵ��Ļ�����Ϣ��', $backurl);
        exit();
}
/*--------------------------
�������Զ���ȡ�ĵ��ؼ���
function makekw(){ }
--------------------------*/
else if($dopost=="makekw")
{
    include_once(DEDEINC.'/splitword.class.php');
    CheckPurview('a_Commend,sys_ArcBatch');
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;

    if($qstr=='')
    {
        ShowMsg("������Ч��", $ENV_GOBACK_URL);
        exit();
    }
    $sp = new SplitWord($cfg_soft_lang, $cfg_soft_lang);
    $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
    $query = "SELECT arc.*, addt.* From `#@__archives` arc LEFT JOIN `#@__addonarticle` addt ON addt.aid=arc.id  WHERE arc.id in($arcids) AND arc.channel=1 ";
    $dsql->SetQuery($query);
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        //�����Ѿ��йؼ��ֵ�����
        if(trim($row['keywords']) !='' ) continue;
        
        $aid = $row['id'];
        $keywords = '';
        $title = $row['title'];
        $description = $row['description'];
        $body = cn_substr($row['body'], 5000);
        $sp->SetSource($title, $cfg_soft_lang, $cfg_soft_lang);
        $sp->StartAnalysis();
        $titleindexs = preg_replace("/#p#|#e#/",'',$sp->GetFinallyIndex());
        $sp->SetSource(Html2Text($body), $cfg_soft_lang, $cfg_soft_lang);
        $sp->StartAnalysis();
        $allindexs = preg_replace("/#p#|#e#/",'',$sp->GetFinallyIndex());
        
        if(is_array($allindexs) && is_array($titleindexs))
        {
            foreach($titleindexs as $k => $v)
            {
                if(strlen($keywords.$k)>=60)
                {
                    break;
                }
                else
                {
                    if(strlen($k) <= 2) continue;
                    $keywords .= $k.',';
                }
            }
            foreach($allindexs as $k => $v)
            {
                if(strlen($keywords.$k)>=60)
                {
                    break;
                }
                else if(!in_array($k,$titleindexs))
                {
                    if(strlen($k) <= 2) continue;
                    $keywords .= $k.',';
                }
            }
        }

        $description = str_replace('��', ' ', trim($description));
        $description = str_replace('��', ' ', $description);
        $description = str_replace('��', ' ', $description);
        $description = preg_replace("#[ \r\n\t]{1,}#is", ' ', $description);
        $description = str_replace('�ؼ���', '', $description);
        $description = str_replace('�ؼ���', '', $description);
        $description = addslashes($description);
        $dsql->ExecuteNoneQuery(" UPDATE `#@__archives` SET `keywords`='$keywords',`description`='$description'  WHERE id='{$aid}' ");
    }
    $sp = null;
    ShowMsg("�ɹ�����ָ���ĵ��Ĺؼ��ʣ�", $ENV_GOBACK_URL);
    exit();
}
/*--------------------------
//������������
function attsAdd(){ }
---------------------------*/
else if($dopost=='attsAdd')
{
    CheckPurview('a_Commend,sys_ArcBatch');
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;

    if($qstr=='')
    {
        ShowMsg("������Ч��",$ENV_GOBACK_URL);
        exit();
    }
    if(empty($flagname))
    {
        ShowMsg("����ָ��Ҫ���ӵ����ԣ�",$ENV_GOBACK_URL);
        exit();
    }
    $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
    $query = "SELECT arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch ON ch.id=tp.channeltype
          WHERE arc.id in($arcids) ";
    $dsql->SetQuery($query);
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        $aid = $row['id'];
        if($row['issystem'] != -1)
        {
            $maintable = ( trim($row['maintable'])=='' ? '#@__archives' : trim($row['maintable']) );
            $arr = $dsql->GetOne("SELECT flag FROM `{$maintable}` WHERE id='$aid' ");
            $flag = ($arr['flag']=='' ? $flagname : $arr['flag'].','.$flagname);
            $dsql->ExecuteNoneQuery(" UPDATE `{$maintable}` SET `flag`='$flag' WHERE id='{$aid}' ");
        }
        else
        {
            $maintable = trim($row['addtable']);
            $arr = $dsql->GetOne("SELECT flag FROM `{$maintable}` WHERE aid='$aid' ");
            $flag = ($arr['flag']=='' ? $flagname : $arr['flag'].','.$flagname);
            $dsql->ExecuteNoneQuery(" UPDATE `{$maintable}` SET `flag`='$flag' WHERE aid='{$aid}' ");
        }
    }
    ShowMsg("�ɹ���ѡ���ĵ�����ָ�������ԣ�",$ENV_GOBACK_URL);
    exit();
}
/*--------------------------
//����ɾ������
function attsDel(){ }
---------------------------*/
else if($dopost=='attsDel')
{
    CheckPurview('a_Commend,sys_ArcBatch');
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;
    if($qstr=='')
    {
        ShowMsg("������Ч��", $ENV_GOBACK_URL);
        exit();
    }
    if(empty($flagname))
    {
        ShowMsg("����ָ��Ҫɾ�������ԣ�", $ENV_GOBACK_URL);
        exit();
    }
    $arcids = preg_replace("#[^0-9,]#", '', preg_replace("#`#", ',', $qstr));
    $query = "SELECT arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch ON ch.id=tp.channeltype
          WHERE arc.id in($arcids) ";
    $dsql->SetQuery($query);
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        $aid = $row['id'];
        if($row['issystem'] != -1)
        {
            $idname = 'id';
            $maintable = ( trim($row['maintable'])=='' ? '#@__archives' : trim($row['maintable']) );
            $arr = $dsql->GetOne("SELECT flag FROM `{$maintable}` WHERE id='$aid' ");
        }
        else
        {
            $idname = 'aid';
            $maintable = trim($row['addtable']);
            $arr = $dsql->GetOne("SELECT flag FROM `{$maintable}` WHERE aid='$aid' ");
        }
        $flag = $arr['flag'];
        if(trim($flag)=='' || !preg_match("#".$flagname."#", $flag) )
        {
            continue;
        }
        else
        {
            $flags  = explode(',', $flag);
            $okflags = array();
            foreach($flags as $f)
            {
                if($f != $flagname) $okflags[] = $f;
            }
        }
        $flag = trim(join(',', $okflags));
        $dsql->ExecuteNoneQuery(" UPDATE `{$maintable}` SET `flag`='$flag' WHERE {$idname}='{$aid}' ");
    }
    ShowMsg("�ɹ���ѡ���ĵ�ɾ��ָ�������ԣ�", $ENV_GOBACK_URL);
    exit();
}
/*--------------------------
//����������Դ�����AJAX����
function attsDlg(){ }
---------------------------*/
else if($dopost=='attsDlg')
{
    if( !empty($aid) && empty($qstr) ) $qstr = $aid;

    $dojobname = ($dojob=='attsDel' ? '����ɾ������' : '������������');
    AjaxHead();
    //���AJAX���ƶ�����
    $divname = 'attsDlg';
    echo "<div class='title' onmousemove=\"DropMoveHand('{$divname}', 225);\" onmousedown=\"DropStartHand();\" onmouseup=\"DropStopHand();\">\r\n";
    echo "    <div class='titLeft'>{$dojobname}</div>\r\n";
    echo "    <div class='titRight'><img src='images/ico-close.gif' style='cursor:pointer;' onclick='HideObj(\"{$divname}\");ChangeFullDiv(\"hide\");' alt='�ر�' title='�ر�' /></div>\r\n";
    echo "</div>\r\n";
    echo "<form name='quickeditform' action='archives_do.php' method='post'>\r\n";
    echo "<input type='hidden' name='dopost' value='{$dojob}' />\r\n";
    echo "<input type='hidden' name='qstr' value='{$qstr}' />\r\n";
    echo "<table width='100%' style='margin-top:6px;z-index:9000;'>\r\n";
?>
<tr height='28'>
    <td width="80" class='bline'>&nbsp;�� �ԣ�</td>
    <td class='bline'>
    <input type='hidden' name='oldflag' value='<?php echo $arcRow['flag']; ?>' />
    <?php
    $dsql->SetQuery("SELECT * FROM `#@__arcatt` ORDER BY sortid ASC");
    $dsql->Execute();
    while($trow = $dsql->GetObject())
    {
        if($trow->att=='j' || $trow->att=='p') continue;
        echo "<input class='np' type='radio' name='flagname' id='flags{$trow->att}' value='{$trow->att}' />{$trow->attname}.{$trow->att}";
    }
    ?>
    </td>
</tr>
<tr height='32'>
    <td width="80" class='bline'>&nbsp;�ĵ�ID��</td>
    <td class='bline'>
        <input type='text' name='tmpids' value="<?php echo $qstr; ?>" style='width:310px;overflow:hidden;' />
    </td>
</tr>
<tr height='32'>
    <td colspan='2' align='center' style='padding-top:12px'>
        <input name="imageField" type="image" src="images/button_ok.gif" width="60" height="22" class="np" border="0" style="cursor:pointer" />
        &nbsp;&nbsp;
        <img src="images/button_back.gif" width="60" height="22" border="0" onclick='HideObj("<?php echo $divname; ?>");ChangeFullDiv("hide");' style="cursor:pointer" />
    </td>
</td>
</tr>
</table>
</form>
<?php
//AJAX�������
}
/*------------------------
function getCatMap() {  }
-------------------------*/
else if($dopost=='getCatMap')
{
    require_once(DEDEINC.'/typeunit.class.selector.php');
    AjaxHead();
    //���AJAX���ƶ�����
    $divname = 'getCatMap';
    echo "<div class='title' style='cursor:default;'>\r\n";
    echo "    <div class='titLeft'>��Ŀ����ѡ����</div>\r\n";
    echo "    <div class='titRight'><img src='images/ico-close.gif' style='cursor:pointer;' onclick='HideObj(\"{$divname}\");ChangeFullDiv(\"hide\");' alt='�ر�' title='�ر�' /></div>\r\n";
    echo "</div>\r\n";
    $tus = new TypeUnitSelector();
?>
<form name='quicksel' action='javascript:;' method='get'>
<div class='quicksel'>
    <?php $tus->ListAllType($channelid); ?>
</div>
<div align='center' class='quickselfoot'>
    <img src="images/button_ok.gif" onclick="getSelCat('<?php echo $targetid; ?>');" width="60" height="22" class="np" border="0" style="cursor:pointer" />
        &nbsp;&nbsp;
    <img src="images/button_back.gif" onclick='HideObj("<?php echo $divname; ?>");ChangeFullDiv("hide");' width="60" height="22" border="0"  style="cursor:pointer" />
</div>
</form>
<?php
//AJAX�������
}
?>