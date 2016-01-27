-- phpMyAdmin SQL Dump
-- version 4.0.10.2
-- http://www.phpmyadmin.net
--
-- 主机: localhost:/tmp/mysql-5.5.40.sock
-- 生成日期: 2015-12-18 07:20:20
-- 服务器版本: 5.5.40
-- PHP 版本: 5.3.28p1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `stamp_ma`
--

-- --------------------------------------------------------

--
-- 表的结构 `stampcustomerlist`
--

CREATE TABLE IF NOT EXISTS `stampcustomerlist` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '编号， 自增长id字段，主键',
  `a_state` varchar(30) NOT NULL COMMENT '注册区域',
  `a_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_estonian_ci NOT NULL COMMENT '姓名',
  `a_company` varchar(100) NOT NULL COMMENT '公司',
  `a_certtype` varchar(30) NOT NULL COMMENT '专业类型',
  `a_certsn` varchar(30) NOT NULL COMMENT '注册号',
  `a_stampsn` varchar(30) NOT NULL COMMENT '印章号',
  `status` tinyint(1) NOT NULL,
  `a_expdate` date NOT NULL COMMENT '有效期至',
  PRIMARY KEY (`a_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

--
-- 转存表中的数据 `stampcustomerlist`
--

INSERT INTO `stampcustomerlist` (`a_id`, `a_state`, `a_name`, `a_company`, `a_certtype`, `a_certsn`, `a_stampsn`, `status`, `a_expdate`) VALUES
(1, '北京', '张三', '北京紫博蓝', '一级注册结构工程师', '99103144', '52221-211', 1, '2015-12-11'),
(2, '北京', '张三', '王小二网络科技有限公司', '注册造价工程师', '1598224', '52221-154', 1, '2016-01-28'),
(3, '北京', '张三', '李四网络科技有限公司', '注册监理工程师', '1598654', '52221-151', 1, '2016-01-29'),
(4, '北京', '张三', '王五网络科技有限公司', '注册土木工程师', '1598894', '52221-141', 1, '2016-01-29'),
(5, '北京', '张三', '赵六网络科技有限公司', '注册电气(供配电)工程师', '1598454', '52221-251', 1, '2016-01-29'),
(6, '北京', '张三', '北京安华建设监理有限责任公司', '一级注册建造师', '00345632', '11006913', 1, '2017-12-26'),
(7, '北京', '张三', '保华建筑（中国）有限公司', '一级注册建造师', '00405690', '44011841', 1, '2016-03-31'),
(8, '北京', '张三1', '北京紫博蓝', '一级注册结构工程师', '99103145', '52221-212', 1, '2015-12-11'),
(9, '北京', '张三2', '王小二网络科技有限公司', '注册造价工程师', '99103146', '52221-213', 1, '2016-01-28'),
(10, '北京', '张三3', '李四网络科技有限公司', '注册监理工程师', '99103147', '52221-214', 1, '2016-01-29'),
(11, '北京', '张三4', '王五网络科技有限公司', '注册土木工程师', '99103148', '52221-215', 1, '2016-01-29'),
(12, '北京', '张三5', '赵六网络科技有限公司', '注册电气(供配电)工程师', '99103149', '52221-216', 1, '2016-01-29'),
(13, '北京', '张三6', '北京安华建设监理有限责任公司', '一级注册建筑师', '99103150', '52221-217', 1, '2017-12-26'),
(14, '北京', '张三7', '保华建筑（中国）有限公司', '一级注册建造师', '99103151', '52221-218', 1, '2016-03-31'),
(15, '北京', '张三8', '保华建筑（中国）有限公司', '一级注册建造师', '99103152', '52221-219', 1, '2016-03-31'),
(16, '北京', '张三9', '保华建筑（中国）有限公司', '一级注册建造师', '99103153', '52221-220', 1, '2016-03-31'),
(17, '北京', '张三10', '保华建筑（中国）有限公司', '一级注册建造师', '99103154', '52221-221', 1, '2016-03-31'),
(18, '北京', '张三11', '保华建筑（中国）有限公司', '一级注册建造师', '99103155', '52221-222', 1, '2016-03-31'),
(19, '北京', '张三12', '保华建筑（中国）有限公司', '一级注册建造师', '99103156', '52221-223', 1, '2016-03-31'),
(20, '北京', '张三13', '保华建筑（中国）有限公司', '一级注册建造师', '99103157', '52221-224', 1, '2016-03-31'),
(21, '北京', '张三14', '保华建筑（中国）有限公司', '公用设备(给水排水)工程师', '99103158', '52221-225', 1, '2016-03-31'),
(22, '北京', '张三15', '保华建筑（中国）有限公司', '公用设备(暖道空调)工程师', '99103159', '52221-226', 1, '2016-03-31'),
(23, '北京', '张三16', '保华建筑（中国）有限公司', '公用设备(动力)工程师', '99103160', '52221-227', 1, '2016-03-31'),
(24, '北京', '张三17', '保华建筑（中国）有限公司', '注册化工工程师', '99103161', '52221-228', 1, '2016-03-31'),
(25, '北京', '张三18', '保华建筑（中国）有限公司', '注册化工工程师', '99103162', '52221-229', 1, '2016-03-31'),
(26, '北京', '张三19', '保华建筑（中国）有限公司', '注册化工工程师', '99103163', '52221-230', 1, '2016-03-31'),
(27, '北京', '张三20', '保华建筑（中国）有限公司', '一级注册建筑师', '99103164', '52221-231', 1, '2016-03-31'),
(28, '北京', '张三21', '保华建筑（中国）有限公司', '一级注册建筑师', '99103165', '52221-232', 1, '2016-03-31'),
(29, '北京', '张三22', '保华建筑（中国）有限公司', '一级注册建筑师', '99103166', '52221-233', 1, '2016-03-31'),
(30, '北京', '张三23', '保华建筑（中国）有限公司', '一级注册结构工程师', '99103167', '52221-234', 1, '2016-03-31');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
