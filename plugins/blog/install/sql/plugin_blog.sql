-- phpMyAdmin SQL Dump
-- version 2.9.1.1-Debian-3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 24. November 2008 um 15:38
-- Server Version: 5.0.32
-- PHP-Version: 5.2.0-8+etch7
-- 
-- Datenbank: `campsite_netgazeti`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur f�r Tabelle `plugin_blog_blog`
-- 

CREATE TABLE `plugin_blog_blog` (
  `blog_id` int(10) unsigned NOT NULL auto_increment,
  `fk_language_id` int(10) unsigned NOT NULL,
  `fk_user_id` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL,
  `published` datetime NOT NULL,
  `info` text NOT NULL,
  `tags` text NOT NULL,
  `admin_remark` text NOT NULL,
  `request_text` text NOT NULL,
  `status` enum('online','offline','moderated') NOT NULL default 'online',
  `admin_status` enum('online','offline','moderated','readonly','pending') NOT NULL default 'pending',
  `entries_online` int(10) unsigned NOT NULL default '0',
  `entries_offline` int(10) unsigned NOT NULL default '0',
  `comments_online` int(10) unsigned NOT NULL,
  `comments_offline` int(10) unsigned NOT NULL,
  `feature` varchar(255) NOT NULL,
  `last_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`blog_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur f�r Tabelle `plugin_blog_comment`
-- 

CREATE TABLE `plugin_blog_comment` (
  `comment_id` int(10) unsigned NOT NULL auto_increment,
  `fk_entry_id` int(10) unsigned NOT NULL default '0',
  `fk_blog_id` int(10) unsigned NOT NULL default '0',
  `fk_language_id` int(10) unsigned NOT NULL,
  `fk_user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `published` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `mood` varchar(255) NOT NULL,
  `status` enum('online','offline','pending') NOT NULL default 'online',
  `admin_status` enum('online','offline','pending') NOT NULL default 'online',
  `feature` varchar(255) NOT NULL,
  `last_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`comment_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur f�r Tabelle `plugin_blog_entry`
-- 

CREATE TABLE `plugin_blog_entry` (
  `entry_id` int(10) unsigned NOT NULL auto_increment,
  `fk_blog_id` int(10) unsigned NOT NULL default '0',
  `fk_language_id` int(10) unsigned NOT NULL,
  `fk_user_id` int(10) unsigned NOT NULL default '0',
  `published` datetime NOT NULL,
  `released` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` text NOT NULL,
  `mood` varchar(255) NOT NULL,
  `status` enum('online','offline') NOT NULL default 'online',
  `admin_status` enum('online','offline','pending') NOT NULL default 'online',
  `comments_online` int(10) unsigned NOT NULL default '0',
  `comments_offline` int(10) unsigned NOT NULL default '0',
  `feature` varchar(255) NOT NULL,
  `last_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`entry_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;