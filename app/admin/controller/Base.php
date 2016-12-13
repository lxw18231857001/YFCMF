<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Common;
use think\Db;
//权限认证
class Base extends Common {
	//初始化
	protected $last_action;
	protected function _initialize(){
        parent::_initialize();
		//未登陆，不允许直接访问
		$aid_s=session('aid');
 		if(empty($aid_s)){
			$this->redirect('admin/Login/login');
		} 
		//已登录，不需要验证的权限
		$not_check = array('Sys/clear','Index/index');//不需要检测的控制器/方法

		//当前操作的请求                 模块名/方法名
		//不在不需要检测的控制器/方法时,检测
		if(!in_array(CONTROLLER_NAME.'/'.ACTION_NAME, $not_check)){
			$auth = new Auth();
			if(!$auth->check(CONTROLLER_NAME.'/'.ACTION_NAME,session('aid')) && session('aid')!= 1){
				$this->error('没有权限',url('Index/index'));
			}
		}
		//获取有权限的菜单tree
		$menus=cache('menus_admin_'.session('aid'));
		if(empty($menus)){
			$auth = new Auth();
			$data = Db::name('auth_rule')->where(array('status'=>1))->order('sort')->select();
			foreach ($data as $k=>$v){
				if(!$auth->check($v['name'], session('aid')) && session('aid') != 1){
					unset($data[$k]);
				}
			}
			$menus=node_merge($data);
			cache('menus_admin_'.session('aid'),$menus);
		}
		$this->assign('menus',$menus);
		//当前方法倒推到顶级菜单数组
		$menus_curr=get_menus_admin();
		//如果$menus_curr为空,则根据'控制器/方法'取status=0的menu
		if(empty($menus_curr)){
			$rst=Db::name('auth_rule')->where(array('status'=>0,'name'=>CONTROLLER_NAME.'/'.ACTION_NAME))->order('level desc,sort')->limit(1)->select();
			if($rst){
				$pid=$rst[0]['pid'];
				//再取父级
				$rst=Db::name('auth_rule')->where(array('id'=>$pid))->find();
				$menus_curr=get_menus_admin($rst['name']);
			}
		}
		$this->assign('menus_curr',$menus_curr);
		//取当前操作菜单父ID
		if(count($menus_curr)>=4){
			$pid=$menus_curr[1];
			$id_curr=$menus_curr[2];
		}elseif(count($menus_curr)>=2){
			$pid=$menus_curr[count($menus_curr)-2];
			$id_curr=end($menus_curr);
		}else{
			$pid='0';
			$id_curr=(count($menus_curr)>0)?end($menus_curr):'';
		}
		//取$pid下子菜单
		$menus_child=Db::name('auth_rule')->where(array('status'=>1,'pid'=>$pid))->order('sort')->select();
		$this->assign('menus_child',$menus_child);
		$this->assign('id_curr',$id_curr);
		$this->assign('admin_avatar',session('admin_avatar'));
	}
}