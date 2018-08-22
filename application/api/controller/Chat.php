<?php
namespace app\api\controller;
use think\Controller;
use think\Request;
use think\Db;

class Chat extends Controller
{
    //文本数据保存
    public function index()
    {
        if(Request::instance()->isAjax()){
            $message=input('post.');
            $datas['fromid']=$message['message']['fromid'];
            $datas['fromname']=$this->getName($message['message']['fromid'])['nickname'];
            $datas['toname']=$this->getName($message['message']['toid'])['nickname'];
            $datas['toid']=$message['message']['toid'];
            $datas['content']=$message['message']['data'];
            $datas['time']=$message['message']['time'];
            $datas['isread']=$message['message']['isread'];
            $datas['type']=1;
            Db::name('communication')->insert($datas);
        }
    }
    //获取用户名
    public function getName($uid){
        $userinfo=Db::name('user')->where("id=".$uid."")->field('nickname')->find();
        return $userinfo;
    }

    //获取用户头像
    public function getHead(){
        if(Request::instance()->isAjax()){
            $fromid=input('fromid');
            $toid=input('toid');
            $frominfo=Db::name('user')->where("id=".$fromid."")->field('headimgurl')->find();
            $toinfo=Db::name('user')->where("id=".$toid."")->field('headimgurl')->find();
            exit(json_encode(array('from_head'=>$frominfo['headimgurl'],'to_head'=>$toinfo['headimgurl']),JSON_UNESCAPED_UNICODE));
        }
    }

    //获取用户名
    public function getNames(){
        if(Request::instance()->isAjax()) {
            $toid=input('toid');
            $userinfo = Db::name('user')->where("id=" . $toid . "")->field('nickname')->find();
            exit(json_encode(array('toname' => $userinfo['nickname']), JSON_UNESCAPED_UNICODE));
        }
    }
    /**
     * 页面加载返回聊天记录
     */
    public function load(){
        if(Request::instance()->isAjax()){
            $fromid = input('fromid');
            $toid = input('toid');
            $count =  Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->count('id');
            if($count>=10){
                $message =    Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->limit($count-10,10)->order('id')->select();
            }else{
                $message =   Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->order('id')->select();
            }
            $imgs=array('.png','.jpeg','.gif','.jpg');
            for($i=0;$i<count($message);$i++){
                $stuffix=strtolower(strchr($message[$i]['content'],'.'));
                $everyStr=$message[$i]['content'];
                if(stripos($everyStr,'.png')||stripos($everyStr,'.jpg')||stripos($everyStr,'.jpeg')||stripos($everyStr,'.gif')){
                    $message[$i]['content']='http://127.0.0.1/workermanTest/public/uploads/'.$message[$i]['content'];
                }
            }
            exit(json_encode(array('message' => $message), JSON_UNESCAPED_UNICODE));

        }
    }
    //图片上传
    public function uploadimg(){
        $file=$_FILES['file'];
        $fromid=input('fromid');
        $toid=input('toid');
        $online=input('online');
        $stuffix=strtolower(strchr($file['name'],'.'));
        $type=array('.gif','.jpg','.jpeg','.png');
        if(!in_array($stuffix,$type)){
            return array('status'=>1,'msg'=>'type error');
        }
        if($file['size']/1024>5120){
            return array('status'=>1,'msg'=>'img is too large');
        }
        $filename=uniqid('chat_img',false);
        $uploadpath=ROOT_PATH.'public\\uploads\\';
        $file_up=$uploadpath.$filename.$stuffix;
        $result=move_uploaded_file($file['tmp_name'],$file_up);
        if($result){
            $name=$filename.$stuffix;
            $data['content']=$name;
            $data['fromid']=$fromid;
            $data['toid']=$toid;
            $data['fromname']=$this->getName($fromid);
            $data['toname']=$this->getName($toid);
            $data['time']=time();
            $data['isread']=$online;
            $data['type']=2;
            $message_id=Db::name('communication')->insertGetId($data);
            if($message_id){
                return array('status'=>0,'msg'=>'success','img_name'=>$name,'url'=>'http://127.0.0.1/workermanTest/public/uploads/'.$filename.$stuffix);
            }else{
                return array('status'=>1,'msg'=>'img error');
            }
        }
    }
}