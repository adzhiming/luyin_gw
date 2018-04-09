模型
<?php  
    namespace Home\Model;
    use Think\Model;
    class UserModel extends Model{
        
        //系统支持数据的批量验证功能，只需要在模型类里面设置patchValidate属性为true（ 默认为false），
        //设置批处理验证后，getError() 方法返回的错误信息是一个数组
        protected $patchValidate = true;
        protected $_validate = array(

            //为了便于模拟，我用的是验证条件是1,也就是在任何条件下都验证,其实所用的字段可能是不存在的
            //是不能够创建数据的
            //内置验证require不能为空
            array('require','require','数据不能为空！',1),
            //内置验证email，验证邮箱格式
            array('email','email','邮箱格式不正确',1),
            //内置验证url,验证网址
            array('url','url','URL地址不正确',1),
            //内置验证currency,验证货币
            array('currency','currency','货币格式不正确',1),
            //内置验证zip,验证邮编
            array('zip','zip','邮政编码不正确',1),
            //内置验证number,验证是不是正整数
            array('number','number','不是正整数',1),
            //内置验证integer,验证是不是整数
            array('integer','integer','不是整数',1),
            //内置验证double,验证是不是浮点数，正负均可
            array('double','double','不是浮点数',1),
            //内置验证english，验证是不是纯英文
            array('english','english','不是纯英文',1),
        );
    }
