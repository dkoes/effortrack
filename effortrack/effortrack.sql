-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 28, 2014 at 09:28 PM
-- Server version: 5.5.38-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `effortrack`
--
CREATE DATABASE IF NOT EXISTS `effortrack` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `effortrack`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `userid` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='admin userids take priority over employee';

-- --------------------------------------------------------

--
-- Table structure for table `effort`
--

CREATE TABLE IF NOT EXISTS `effort` (
  `week` date NOT NULL,
  `userid` varchar(255) NOT NULL,
  `effort` int(11) NOT NULL,
  `center` varchar(255) NOT NULL COMMENT 'cost center',
  `project` varchar(255) NOT NULL,
  UNIQUE KEY `week` (`week`,`userid`,`project`),
  KEY `week_2` (`week`),
  KEY `userid` (`userid`),
  KEY `center` (`center`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This is like a log of data - store everyting in each row';

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE IF NOT EXISTS `employees` (
  `userid` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `Last` text NOT NULL,
  `First` text NOT NULL,
  `center` varchar(255) NOT NULL,
  PRIMARY KEY (`userid`),
  KEY `center` (`center`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores available projects with their categories.';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
