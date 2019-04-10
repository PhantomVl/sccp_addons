-
-- this is for users how like to sepatet device and button configuration
-- You have to change the table names to:
--
CREATE TABLE `sccpuser` (
	`name` VARCHAR(20) NOT NULL DEFAULT '',
	`pin` VARCHAR(7) NULL DEFAULT NULL,
	`password` VARCHAR(7) NULL DEFAULT NULL,
	`description` VARCHAR(45) NULL DEFAULT NULL,
	`roaminglogin` ENUM('on','off','multi') NULL DEFAULT 'off',
	`devicegroup` VARCHAR(7) NOT NULL,
	`auto_logout` ENUM('on','off') NULL DEFAULT 'off',
	`homedevice` VARCHAR(20) NULL DEFAULT NULL,
	PRIMARY KEY (`name`),
	UNIQUE INDEX `name` (`name`)
) ENGINE=INNODB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `sccpbuttonconfig` (
	`ref` VARCHAR(15) NOT NULL DEFAULT '',
	`reftype` ENUM('sccpdevice','sccpuser') NOT NULL DEFAULT 'sccpdevice',
	`instance` TINYINT(4) NOT NULL DEFAULT '0',
	`buttontype` ENUM('line','speeddial','service','feature','empty') NOT NULL DEFAULT 'line',
	`name` VARCHAR(36) NULL DEFAULT NULL,
	`options` VARCHAR(100) NULL DEFAULT NULL,
	PRIMARY KEY (`ref`, `reftype`, `instance`, `buttontype`),
	INDEX `ref` (`ref`, `reftype`)
) ENGINE=INNODB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TRIGGER IF EXISTS sccp_trg_buttonconfig;
DELIMITER $$
CREATE TRIGGER trg_buttonconfig BEFORE INSERT ON sccpbuttonconfig
FOR EACH ROW
BEGIN
     IF NEW.`reftype` = 'sccpdevice' THEN
        IF (SELECT COUNT(*) FROM `sccpdevice` WHERE `sccpdevice`.`name` = NEW.`ref` ) = 0 THEN
           UPDATE `Foreign key contraint violated: ref does not exist in sccpdevice` SET x=1;
        END IF;
     END IF;
     IF NEW.`reftype` = 'sccpline' THEN
        IF (SELECT COUNT(*) FROM `sccpline` WHERE `sccpline`.`name` = NEW.`ref`) = 0 THEN
             UPDATE `Foreign key contraint violated: ref does not exist in sccpline` SET x=1;
         END IF;
     END IF;
     IF NEW.`buttontype` = 'line' THEN
         SET @line_x = SUBSTRING_INDEX(NEW.`name`,'!',1);
         SET @line_x = SUBSTRING_INDEX(@line_x,'@',1);
         IF (SELECT COUNT(*) FROM `sccpline` WHERE `sccpline`.`name` = @line_x ) = 0 THEN
             UPDATE `Foreign key contraint violated: line does not exist in sccpline` SET x=1;
         END IF;
     END IF;
END$$
DELIMITER ;

CREATE TABLE `sccpdevice` (
	`type` VARCHAR(15) NULL DEFAULT NULL,
	`addon` VARCHAR(45) NULL DEFAULT NULL,
	`_description` VARCHAR(45) NULL DEFAULT NULL,
	`tzoffset` VARCHAR(5) NULL DEFAULT NULL,
	`imageversion` VARCHAR(31) NULL DEFAULT NULL,
	`deny` VARCHAR(100) NULL DEFAULT '0.0.0.0/0.0.0.0',
	`permit` VARCHAR(100) NULL DEFAULT 'internal',
	`earlyrtp` ENUM('immediate','offhook','dialing','ringout','progress','none') NULL DEFAULT NULL,
	`mwilamp` ENUM('on','off','wink','flash','blink') NULL DEFAULT 'on',
	`mwioncall` ENUM('on','off') NULL DEFAULT 'on',
	`dndFeature` ENUM('on','off') NULL DEFAULT NULL,
	`transfer` ENUM('on','off') NULL DEFAULT NULL,
	`cfwdall` ENUM('on','off') NULL DEFAULT 'on',
	`cfwdbusy` ENUM('on','off') NULL DEFAULT 'on',
	`private` ENUM('on','off') NOT NULL DEFAULT 'off',
	`privacy` ENUM('full','on','off') NOT NULL DEFAULT 'full',
	`nat` ENUM('on','off','auto') NULL DEFAULT NULL,
	`directrtp` ENUM('on','off') NULL DEFAULT NULL,
	`softkeyset` VARCHAR(100) NULL DEFAULT 'softkeyset',
	`audio_tos` VARCHAR(11) NULL DEFAULT '0xB8',
	`audio_cos` VARCHAR(1) NULL DEFAULT '6',
	`video_tos` VARCHAR(11) NULL DEFAULT '0x88',
	`video_cos` VARCHAR(1) NULL DEFAULT '5',
	`conf_allow` ENUM('on','off') NOT NULL DEFAULT 'on',
	`conf_play_general_announce` VARCHAR(3) NULL DEFAULT 'on',
	`conf_play_part_announce` ENUM('on','off') NOT NULL DEFAULT 'on',
	`conf_mute_on_entry` ENUM('on','off') NOT NULL DEFAULT 'off',
	`conf_music_on_hold_class` VARCHAR(80) NULL DEFAULT 'default',
	`conf_show_conflist` ENUM('on','off') NOT NULL DEFAULT 'on',
	`force_dtmfmode` ENUM('auto','rfc2833','skinny') NOT NULL DEFAULT 'auto',
	`setvar` VARCHAR(100) NULL DEFAULT NULL,
	`backgroundImage` VARCHAR(255) NULL DEFAULT NULL,
	`ringtone` VARCHAR(255) NULL DEFAULT NULL,
	`name` VARCHAR(15) NOT NULL DEFAULT '',
	`callhistory_answered_elsewhere` ENUM('Ignore','Missed Calls','Received Calls','Placed Calls') NULL DEFAULT NULL,
	`_hwlang` VARCHAR(12) NULL DEFAULT NULL,
	`_loginname` VARCHAR(20) NULL DEFAULT NULL,
	`_profileid` INT(11) NOT NULL DEFAULT '0',
	`useRedialMenu` VARCHAR(5) NULL DEFAULT 'no',
	`phonecodepage` VARCHAR(50) NULL DEFAULT NULL,
	PRIMARY KEY (`name`)
) ENGINE=INNODB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;



-- CREATE OR REPLACE 
-- ALGORITHM = MERGE
-- VIEW sccpdeviceconfig AS
-- SELECT  case sccpdevice._profileid 
--            when 0 then 
--    		(select GROUP_CONCAT(CONCAT_WS( ',', defbutton.buttontype, defbutton.name, defbutton.options ) SEPARATOR ';') from `sccpbuttonconfig` as defbutton where defbutton.ref = sccpdevice.name ORDER BY defbutton.instance )
--    	when 1 then 			
--    		(select GROUP_CONCAT(CONCAT_WS( ',', userbutton.buttontype, userbutton.name, userbutton.options ) SEPARATOR ';') from `sccpbuttonconfig` as userbutton where userbutton.ref = sccpdevice._loginname ORDER BY userbutton.instance ) 
--    	when 2 then 			
--		(select GROUP_CONCAT(CONCAT_WS( ',', homebutton.buttontype, homebutton.name, homebutton.options ) SEPARATOR ';') from `sccpbuttonconfig` as homebutton where homebutton.ref = sccpuser.homedevice  ORDER BY homebutton.instance ) 
--            end as button,  if(sccpdevice._profileid = 0, sccpdevice._description, sccpuser.description) as description, sccpdevice.*
-- FROM sccpdevice
-- LEFT JOIN sccpuser sccpuser ON ( sccpuser.name = sccpdevice._loginname )
-- GROUP BY sccpdevice.name;

CREATE OR REPLACE 
ALGORITHM = MERGE 
VIEW sccpdeviceconfig AS
     SELECT GROUP_CONCAT( CONCAT_WS( ',', sccpbuttonconfig.buttontype, sccpbuttonconfig.name, sccpbuttonconfig.options )
     ORDER BY instance ASC SEPARATOR ';' ) AS sccpbutton, sccpdevice.*
     FROM sccpdevice
     LEFT JOIN sccpbuttonconfig ON (sccpbuttonconfig.reftype = 'sccpdevice' AND sccpbuttonconfig.ref = sccpdevice.name )
GROUP BY sccpdevice.name;

CREATE OR REPLACE ALGORITHM = MERGE 
VIEW sccpuserconfig AS
     SELECT GROUP_CONCAT( CONCAT_WS( ',', sccpbuttonconfig.buttontype, sccpbuttonconfig.name, sccpbuttonconfig.options )
     ORDER BY instance ASC SEPARATOR ';' ) AS button, sccpuser.*
     FROM sccpuser
     LEFT JOIN sccpbuttonconfig ON ( sccpbuttonconfig.reftype = 'sccpuser' AND sccpbuttonconfig.ref = sccpuser.id)
GROUP BY sccpuser.name; 
