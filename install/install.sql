/* Relaciona el ID local del carrier con el ID remoto */
CREATE TABLE IF NOT EXISTS `PREFIX_enviopack_carrier`	(
  `id_carrier` INT NOT NULL AUTO_INCREMENT,
  `id_local_carrier`	INT NOT	NULL,
  `id_remote_carrier`	VARCHAR(100) NOT	NULL,
  `has_relaypoint` TINYINT,
  `description` VARCHAR(200) NOT NULL,
  `service_type` VARCHAR(1) NOT NULL,
  `modality` VARCHAR(1) NOT NULL,
  `active` TINYINT NOT NULL,
  PRIMARY	KEY	(`id_carrier`)
)	ENGINE=InnoDB	DEFAULT	CHARSET=utf8 AUTO_INCREMENT=1;

/* Lista de sucursales de cada correo */
CREATE TABLE IF NOT EXISTS `PREFIX_enviopack_relaypoint`	(
  `id_relaypoint` INT AUTO_INCREMENT,
  `id_carrier` INT,
  `id_remote_relay` INT NOT NULL,
  `id_remote_carrier` VARCHAR(100) NOT NULL,
  `description` VARCHAR(60) NOT NULL,
  `street` VARCHAR(60) NOT NULL,
  `number` VARCHAR(15) NOT NULL,
  `floor` VARCHAR(15) NOT NULL,
  `department` VARCHAR(15) NOT NULL,
  `locality` VARCHAR(60) NOT NULL,
  `postal_code` VARCHAR(60) NOT NULL,
  `latitude` VARCHAR(15) NOT NULL,
  `longitude` VARCHAR(15) NOT NULL,
  PRIMARY KEY(`id_relaypoint`),
  FOREIGN KEY (`id_carrier`) REFERENCES `PREFIX_enviopack_carrier`(`id_carrier`) ON UPDATE CASCADE ON DELETE CASCADE
)	ENGINE=InnoDB	DEFAULT	CHARSET=utf8;

/* Tabla de tránsito de los peidos que se envían a sucursal */
CREATE TABLE IF NOT EXISTS `PREFIX_enviopack_shipping_relaypoint`	(
  `id_relaypoint` int(11) NOT NULL,
  `id_cart` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `service` varchar(50) NOT NULL,
  `address` varchar(128) NOT NULL,
  `price` float NOT NULL
)	ENGINE=InnoDB	DEFAULT	CHARSET=utf8;

/* Tabla donde se guarda la relación pedido envio de enviopack*/
CREATE TABLE IF NOT EXISTS `PREFIX_enviopack_order` (
  `id_ps_order`	INT	NOT	NULL,
  `id_ep_order`	INT,
  `id_shipment`	INT,
  `street`	VARCHAR(100),
  `number`	VARCHAR(100),
  `floor`	VARCHAR(100),
  `department`	VARCHAR(100),
  `carrier_id`	int
)	ENGINE=InnoDB	DEFAULT	CHARSET=utf8;
