SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `tms`
--

-- Drop tables if they exist to avoid #1050 errors
DROP TABLE IF EXISTS `tblreviews`;
DROP TABLE IF EXISTS `tblusers`;
DROP TABLE IF EXISTS `tbltourpackages`;
DROP TABLE IF EXISTS `tblpages`;
DROP TABLE IF EXISTS `tblissues`;
DROP TABLE IF EXISTS `tblenquiry`;
DROP TABLE IF EXISTS `tblbooking`;
DROP TABLE IF EXISTS `admin`;

--
-- Table structure for table `admin`
--
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `admin`
--
INSERT INTO `admin` (`id`, `UserName`, `Password`, `updationDate`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', '2025-01-20 05:38:35');

--
-- Table structure for table `tblbooking`
--
CREATE TABLE IF NOT EXISTS `tblbooking` (
  `BookingId` int(11) NOT NULL AUTO_INCREMENT,
  `PackageId` int(11) DEFAULT NULL,
  `UserEmail` varchar(100) DEFAULT NULL,
  `FromDate` varchar(100) DEFAULT NULL,
  `ToDate` varchar(100) DEFAULT NULL,
  `Comment` mediumtext DEFAULT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `status` int(11) DEFAULT NULL,
  `CancelledBy` varchar(5) DEFAULT NULL,
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  -- Added new columns
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`BookingId`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblbooking`
--
INSERT INTO `tblbooking` (`BookingId`, `PackageId`, `UserEmail`, `FromDate`, `ToDate`, `Comment`, `RegDate`, `status`, `CancelledBy`, `UpdationDate`, `PaymentMethod`, `PaymentDate`) VALUES
(1, 1, 'renulg@gmail.com', '2025-01-06', '2025-01-09', 'I want to travel Upper Mustang', '2025-01-05 08:57:52', 1, NULL, '2025-01-30 14:23:30', NULL, NULL),
(2, 10, 'lgrenu6@gmail.com', '2025-01-01', '2025-01-05', 'I want to book for Rara Tour', '2025-01-27 12:00:48', 2, 'a', '2025-01-30 14:05:07', NULL, NULL),
(6, 3, 'alinarai@gmail.com', '2025-01-01', '2025-01-05', ' I want to book for group package (25 people) ', '2025-01-30 14:16:17', 2, 'u', '2025-01-30 14:20:43', NULL, NULL),
(7, 3, 'alinarai@gmail.com', '2025-01-03', '2025-01-10', ' I want to book for group package (25 people) ', '2025-01-30 14:20:08', 0, NULL, NULL, NULL, NULL),
(8, 1, 'bipana7@gmail.com', '2025-01-01', '2025-01-07', ' I want to book for family package (5 people) ', '2025-01-31 01:20:54', 0, NULL, NULL, NULL, NULL);

--
-- Table structure for table `tblenquiry`
--
CREATE TABLE IF NOT EXISTS `tblenquiry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) DEFAULT NULL,
  `EmailId` varchar(100) DEFAULT NULL,
  `MobileNumber` char(10) DEFAULT NULL,
  `Subject` varchar(100) DEFAULT NULL,
  `Description` mediumtext DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `Status` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblenquiry`
--
INSERT INTO `tblenquiry` (`id`, `FullName`, `EmailId`, `MobileNumber`, `Subject`, `Description`, `PostingDate`, `Status`) VALUES
(1, 'Raju Singh', 'stjraj2016@gmail.com', '9808819373', 'booking', 'I want to know the departure date of travelling', '2025-01-30 13:01:06', 1);

--
-- Table structure for table `tblissues`
--
CREATE TABLE IF NOT EXISTS `tblissues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserEmail` varchar(100) DEFAULT NULL,
  `Issue` varchar(100) DEFAULT NULL,
  `Description` mediumtext DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `AdminRemark` mediumtext DEFAULT NULL,
  `AdminremarkDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblissues`
--
INSERT INTO `tblissues` (`id`, `UserEmail`, `Issue`, `Description`, `PostingDate`, `AdminRemark`, `AdminremarkDate`) VALUES
(1, 'alinarai@gmail.com', 'Cancellation', 'I want to cancel my booking', '2025-01-30 14:00:31', NULL, NULL),
(13, NULL, NULL, NULL, '2025-01-30 14:11:56', NULL, NULL);

--
-- Table structure for table `tblpages`
--
CREATE TABLE IF NOT EXISTS `tblpages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT '',
  `detail` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = MyISAM DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblpages`
--
INSERT INTO `tblpages` (`id`, `type`, `detail`) VALUES
(1, 'aboutus', '<div><span style="color: rgb(0, 0, 0); font-family: Georgia; font-size: 15px; text-align: justify; font-weight: bold;">Welcome to Tour and Travel Agency !!!</span></div><span style="font-family: "times new roman"; font-size: large;"><span style="color: rgb(0, 0, 0); text-align: justify;">Since then, our courteous and committed team members have always ensured a pleasant and enjoyable tour for the clients. This arduous effort has enabled Tour & Travels to be recognized as a dependable Travel Solutions provider with three offices in Kathmandu.</span><span style="color: rgb(80, 80, 80);"> We have got packages to suit the discerning traveler\'s budget and savor. Book your dream vacation online. Supported quality and proposals of our travel consultants, we have a tendency to welcome you to decide on from holidays packages and customize them according to your plan.</span></span>'),
(2, 'contact', '<span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px; text-align: justify;"><span style="font-weight: bold;">Address</span>:</span><div style="text-align: justify;"><span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px;">Sundhara, Kathmandu</span></div><div style="text-align: justify;"><span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px;"><span style="font-weight: bold;">Landline NO</span>. :- 01-4521274, 01-4520577</span></div><div style="text-align: justify;"><span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px;"><span style="font-weight: bold;">Cell No.</span> :- 9808819373, 9844114017</span></div><div style="text-align: justify;"><span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px;"><span style="font-weight: bold;">Email:</span>- ttaplanner@gmail.com</span></div><div style="text-align: justify;"><span style="color: rgb(0, 0, 0); font-family: "Open Sans", Arial, sans-serif; font-size: 14px;"><span style="font-weight: bold;">Website:</span>- www.ttaplanner.com.np</span></div>');

--
-- Table structure for table `tbltourpackages`
--
CREATE TABLE IF NOT EXISTS `tbltourpackages` (
  `PackageId` int(11) NOT NULL AUTO_INCREMENT,
  `PackageName` varchar(200) DEFAULT NULL,
  `PackageType` varchar(150) DEFAULT NULL,
  `PackageLocation` varchar(100) DEFAULT NULL,
  `PackagePrice` int(11) DEFAULT NULL,
  `PackageFetures` varchar(255) DEFAULT NULL,
  `PackageDetails` mediumtext DEFAULT NULL,
  `PackageImage` varchar(100) DEFAULT NULL,
  `Creationdate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  -- Added new columns
  `PackageDuration` int(11) NOT NULL DEFAULT 1,
  `MaxSlots` int(11) NOT NULL DEFAULT 10,
  `locationType` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`PackageId`),
  CONSTRAINT `positive_duration` CHECK (PackageDuration > 0),
  CONSTRAINT `positive_slots` CHECK (MaxSlots >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tbltourpackages`
--
INSERT INTO `tbltourpackages` (`PackageId`, `PackageName`, `PackageType`, `PackageLocation`, `PackagePrice`, `PackageFetures`, `PackageDetails`, `PackageImage`, `Creationdate`, `UpdationDate`, `PackageDuration`, `MaxSlots`, `locationType`) VALUES
(1, 'Upper Mustang', 'Group', 'Upper Mustang', 19999, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'UPPER MUSTANG TOUR\r\n5Nights/6Days By 4WD jeep:- NPR 20,999/- Per Person.\r\n6Nights/7Days By 4WD jeep:- NPR 24,999/- Per Person.\r\nTRIP HIGHLIGHT: ( Visit Upper Mustang, Visit palace in Lo-Manthang, Get stunning view of high cliff, 4WD Off-Road Drive Experience, Hiking for 1 to 2 hours, Landscape & Amazing Mountain View, Visit Nepal-China Border, Breathtaking Scenary)', 'uppermustang.jpg', '2025-01-06 14:07:14', '2025-01-30 13:24:10', 7, 5, 'Mountain, Adventure, Trekking'),
(2, 'Muktinath', 'Spiritual', 'Muktinath', 11499, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'MUKTINATH TOUR\r\n4Nights/5Days: NPR 11499/- Per Person.\r\nTRIP HIGHLIGHT:\r\nMuktinath Mandir Darshan\r\n( Bathing at one of the most popular 108 Dhara at Muktinath, Visit to Marpha Village & jeri galli,\r\nDhumbu Lake & Jomsom bazaar sight seen, Visit to Kagbeni, Nepal’s Longest suspension bridge at Kushma, Baglung Kalika Mandir Darshan, Sightseen at Spiritual place Tatopani, Galeshwor Mandir darshan, Pokhara Sight seen)', 'muktinath.jpg', '2025-01-01 08:13:10', '2025-01-25 13:25:02', 5, 7, 'Religious, Mountain, Spiritual'),
(3, 'Rara  Tour ', 'Group', 'Jumla & Mugu  District', 18999, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'RARA TOUR 6Nights/7Days : 18999/- Per Person. \r\nTRIP HIGHLIGHT: ( Visit Nepal’s biggest Lake, Stunning Mountain View, Hike inside the conservation area, 4WD Off-Road Drive Experience, Hiking for 1 to 2 hours, Beautiful Landscape & amazing view, Snow activities [During Snowfall], Breathtaking Scenery )', 'rara lake.jpg', '2024-11-27 08:37:58', NULL, 7, 4, 'Lake, Mountain, Adventure'),
(4, 'Mardi Himal', 'Group', 'East side of Annapurna Base Camp', 13500, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'MARDI HIMAL TREK 4Nights/5dAYS: 13,500/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Drive to Dhamphus & hike to Deurali, Deurali to Low Camp Trek, Low Camp to High Camp Trek, High Camp to view point and back to Forest Camp Trek, Low Camp to Dhamphus and Drive to Kathmandu )', 'mardi himal trek.jpg', '2025-01-27 08:47:10', '2025-01-30 13:26:15', 5, 6, 'Mountain, Trekking, Adventure'),
(5, 'Pokhara Tour ', 'Couple', 'Pokhara', 8000, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'POKHARA TOUR 2Nights/3Days: 8000/- Per Person.\r\nTRIP HIGHLIGHT: ( Visit touristic hub Pokhara city, Sight seeing [ Sarangkot , PhewaLake Davids falls, Pudimkot Shiva Statue, Peace Pagoda, Mahendra Cave ], Evening walk in Lakeside area )', 'pokhara.jpg', '2025-01-27 08:56:29', NULL, 3, 8, 'City, Lake, Sightseeing'),
(6, 'Tilicho Lake Tour', 'Couple', 'Manang District', 18999, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'TILICHO LAKE TOUR 5Nights/6Days: 18999/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Kathmandu to Taal, Besisahar to Manang, Manang/Khangsar to Tilicho base camp [ trek], TBC- tilicho lake- TBC [trek], TBC to chame/chamche, Chame/chamche to Kathmandu )', 'tilicho-lake.jpg', '2025-01-27 09:08:22', NULL, 6, 3, 'Lake, Mountain, Trekking, Adventure'),
(7, 'Pathivara  Darshan ', 'Spiritual', 'Taplejung District', 12000, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'PATHIVARA DARSHAN 3Nights/4Days: 13500/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Kathmandu to Fikkal, Fikkal to taplejung [ thulo phedi ], Taplejung - Pathivara - Phedim, Phedim to Ithari, Ithari to Kathmandu )', 'pathivara.jpg', '2025-01-27 09:22:58', NULL, 4, 9, 'Religious, Mountain, Spiritual'),
(8, 'Annapurna Base Camp (ABC)', 'Group', 'Kaski District', 17999, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'ANNAPURNA BASE CAMP 6Nights/7Days: 17999/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Kathmandu to Ghandruk, Ghandruk to Chhomrong, Chhomrong to Himalaya, Himalaya to ABC, ABC to lower sinuwa/bhunuwa, Sinuwa/bhunuwa to Pokhara, Pokhara to Kathmandu )', 'ABC image.jpg', '2025-01-27 09:32:21', NULL, 7, 5, 'Mountain, Trekking, Adventure'),
(9, 'Poonhill Trek', 'Group', 'Myagdi District', 12999, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'POONHILL TREK 4Nights/5Days: 12999/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Kathmandu to Pokhara, Pokhara to Ulleri, Ulleri to Ghorepani, Ghorepani - Poonhill to Pokhara, Pokhara to Kathmandu )', 'poon hill trek.jpeg', '2025-01-27 09:39:00', NULL, 5, 6, 'Mountain, Trekking, Adventure'),
(10, 'Gosaikunda Trek', 'Group', 'Rasuwa District', 12500, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'GOSAIKUNDA TREK 4Nights/5Days: 12500/- Per Person.\r\nTRIP HIGHLIGHT:\r\n(Drive to Kathmandu to Dhunche, Trek to Chandanbari, Trek to Gosaikunda & back to lauribina yak, Trek to down Dhunche, Drive to Kathmandu )', 'gosaikunda-lake.jpg', '2025-01-27 09:48:01', NULL, 5, 7, 'Lake, Trekking, Adventure'),
(11, 'Kalinchowk Tour', 'Spiritual', 'Dolakha District', 3500, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'KALINCHOWK TOUR 1Nights/2Days: 3500/- Per Person.\r\nTRIP HIGHLIGHT:\r\n( Drive Kathmandu to Kalinchowk Bhagwati Temple, Dolakha Bhimsen Temple, Breathtaking views of mountains from Gaurisankhari himal, Langtang range with beautiful sunrise, Sightseeing at Kharedhunga, Return to Kathmanadu with lots of memories )', 'kalinchowk.jpg', '2025-01-27 09:56:26', NULL, 2, 10, 'Mountain, Adventure, Spiritual'),
(12, 'Sukute Beach', 'Couple', 'Sindhupalchowk District', 2500, 'Grounded Transportation, Hotels/Accommodation, Lunch, Dinner & Breakfast, Campfire, Music, Dance, Experience Guide, Photos & Video', 'SUKUTE BEACH 1Nights/2Days: RS 2500/- Per Person. \r\nTRIP HIGHLIGHT:\r\n(  Drive Kathmandu to Sukute via  Koteshswor - Dhulikhel,  Arrive at Sukute  resort & refreshment welcome snacks,   \r\nJungle Walks/Rafting, Drive back to Kathmandu )', 'sukute-beach.jpg', '2025-01-27 10:13:37', NULL, 2, 8, 'River, Adventure'),
(13, 'Sikkim Explorer', 'Group', 'Sikkim', 24999, 'Hotel Accommodation, Ground Transportation, Breakfast', 'SIKKIM EXPLORER TOUR\n6 Nights/7 Days:\n- Gangtok, Tsomgo Lake, Nathu La Pass', 'sikkim.jpg', '2025-05-24 20:16:49', '2025-06-11 08:01:19', 7, 10, 'Mountain, Hill, Sightseeing'),
(14, 'Chitwan Wildlife Discovery', 'Group', 'Sauraha, Chitwan, Nepal', 11000, 'Wildlife Exploration: Jeep safari, canoe ride, and cultural dance', 'Day 1: Arrival in Chitwan and Cultural Introduction\nDay 2: Jungle Safari and Canoeing\nDay 3: Return to Kathmandu', 'chitwan.jpg', '2025-05-26 07:50:35', '2025-06-11 08:00:08', 3, 3, 'Jungle, Wildlife, Culture');

--
-- Table structure for table `tblusers`
--
CREATE TABLE IF NOT EXISTS `tblusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) DEFAULT NULL,
  `MobileNumber` char(10) DEFAULT NULL,
  `EmailId` varchar(70) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  -- Added new column
  `session_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`EmailId`),
  INDEX `idx_session_token` (`session_token`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblusers`
--
INSERT INTO `tblusers` (`id`, `FullName`, `MobileNumber`, `EmailId`, `Password`, `RegDate`, `UpdationDate`, `session_token`) VALUES
(1, 'Renuu', '9817172622', 'renulg@gmail.com', 'ef88f3f374aa10d1493757bb6a4046a6', '2025-01-05 08:57:21', NULL, NULL),
(2, 'Renu L.G.', '9808819373', 'lgrenu6@gmail.com', '9b5dd21ad99123b59695432776bc5ed8', '2025-01-05 12:17:57', NULL, NULL),
(3, 'Raju Singh L.G.', '9844113017', 'stjraj2016@gmail.com', '67719c4c2dae2189c6a83110e9461c15', '2025-01-30 13:53:35', NULL, NULL),
(4, 'Alina Rai', '9812345678', 'alinarai@gmail.com', '68e1efaa20fc3057a144cd5542377a25', '2025-01-30 13:54:42', '2025-01-30 14:10:25', NULL),
(13, 'Bipana Aryl', '9802134567', 'bipana7@gmail.com', 'ab5c19219dccca7c4c77c593307aa628', '2025-01-30 14:11:56', NULL, NULL);

--
-- Table structure for table `tblreviews`
--
CREATE TABLE IF NOT EXISTS `tblreviews` (
  `ReviewId` int(11) NOT NULL AUTO_INCREMENT,
  `PackageId` int(11) DEFAULT NULL,
  `UserEmail` varchar(70) DEFAULT NULL,
  `Rating` int(1) DEFAULT NULL COMMENT '1 to 5 stars',
  `Comment` mediumtext DEFAULT NULL,
  `ReviewDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` int(1) DEFAULT 0 COMMENT '0: Pending, 1: Approved, 2: Rejected',
  PRIMARY KEY (`ReviewId`),
  FOREIGN KEY (`PackageId`) REFERENCES `tbltourpackages` (`PackageId`) ON DELETE CASCADE,
  FOREIGN KEY (`UserEmail`) REFERENCES `tblusers` (`EmailId`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

--
-- Dumping data for table `tblreviews`
--
INSERT INTO `tblreviews` (`ReviewId`, `PackageId`, `UserEmail`, `Rating`, `Comment`, `ReviewDate`, `Status`) VALUES
(1, 1, 'renulg@gmail.com', 4, 'Amazing Upper Mustang tour! The jeep ride was thrilling, and the views were breathtaking. Only issue was the accommodation could be better.', '2025-01-10 10:00:00', 1),
(2, 3, 'alinarai@gmail.com', 5, 'Rara Lake was stunning! The guide was knowledgeable, and the group had a great time. Highly recommend!', '2025-01-06 12:30:00', 1),
(3, 5, 'bipana7@gmail.com', 3, 'Pokhara tour was fun, but the itinerary felt rushed. Lakeside was the highlight.', '2025-01-02 15:45:00', 0);

--
-- AUTO_INCREMENT for dumped tables
--
ALTER TABLE `admin` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 2;
ALTER TABLE `tblbooking` MODIFY `BookingId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 9;
ALTER TABLE `tblenquiry` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 6;
ALTER TABLE `tblissues` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 14;
ALTER TABLE `tblpages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 22;
ALTER TABLE `tbltourpackages` MODIFY `PackageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 24; -- Adjusted to 24 to account for PackageId 23
ALTER TABLE `tblusers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 14;
ALTER TABLE `tblreviews` MODIFY `ReviewId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 4;
ALTER TABLE tblbooking
ADD COLUMN CancelReason VARCHAR(500) NULL AFTER CancelledBy;

UPDATE tbltourpackages
SET PackageType = 'Group'
WHERE PackageName LIKE '%Chitwan%';

-- Select minimum and maximum package prices
SELECT MIN(PackagePrice), MAX(PackagePrice) FROM tbltourpackages;

COMMIT;