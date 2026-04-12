#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
GeoNames city data loader.

Loads city data from GeoNames files and imports them into a MySQL database.
"""

import sys
from typing import List, Optional, Tuple, Dict, Any

# Import MySQL database driver
try:
    import MySQLdb as db
except ImportError as e:
    sys.stderr.write(f"Cannot load MySQL database interface: {e}\n")
    sys.exit(1)

# Import configuration
try:
    from geocities_dsn import cs_dsn
except ImportError as e:
    sys.stderr.write(f"Cannot import MySQL DSN specification: {e}\n")
    sys.exit(1)

try:
    from geocities_src import geo_file_name
except ImportError as e:
    sys.stderr.write(f"Cannot import GeoNames file specification: {e}\n")
    sys.exit(1)


# Column indices for parsing GeoNames data files
LINECOLUMNS = 19
(
    LCOL_ID, LCOL_MAIN, LCOL_ASCII, LCOL_OTHER, 
    LCOL_LAT, LCOL_LON, LCOL_FCLASS, LCOL_FCODE
) = list(range(8))
(
    LCOL_CC, LCOL_CCOTHER, LCOL_A1C, LCOL_A2C, LCOL_A3C, LCOL_A4C
) = list(range(8, 14))
(
    LCOL_POP, LCOL_ELE, LCOL_ELEAVG, LCOL_TZONE
) = list(range(14, 18))
LCOL_MOD = 18

# Database column definitions
CITY_COLUMNS = ['id', 'city_type', 'population', 'elevation', 'country_code', 'time_zone']
CITY_COLUMNS_STR = ", ".join(CITY_COLUMNS)
CITY_QM = ['%s'] * len(CITY_COLUMNS)
CITY_QM_STR = ", ".join(CITY_QM)

NAME_COLUMNS = ['fk_citylocations_id', 'city_name', 'name_type']
NAME_COLUMNS_STR = ", ".join(NAME_COLUMNS)
NAME_QM = ['%s'] * len(NAME_COLUMNS)
NAME_QM_STR = ", ".join(NAME_QM)

# SQL INSERT statements
CITY_INSERT = f"INSERT INTO CityLocations (position, {CITY_COLUMNS_STR}) VALUES (%s, {CITY_QM_STR})"
NAME_INSERT = f"INSERT INTO CityNames ({NAME_COLUMNS_STR}) VALUES ({NAME_QM_STR})"


def log_error(msg: str) -> None:
    """
    Log an error message to stderr.
    
    Args:
        msg: Error message to log
    """
    try:
        if not msg.endswith("\n"):
            msg += "\n"
        sys.stderr.write(msg)
        sys.stderr.flush()
    except Exception:
        pass


class GeoNamesLoader:
    """Handles loading GeoNames data into a MySQL database."""
    
    def __init__(self):
        """Initialize the loader with no database connection."""
        self.dbconn = None
        self.dbcurs = None
    
    def db_disconnect(self) -> None:
        """Close the database connection."""
        try:
            if self.dbcurs:
                self.dbcurs.close()
            if self.dbconn:
                self.dbconn.close()
        except Exception:
            pass
        
        self.dbcurs = None
        self.dbconn = None
    
    def db_connect(self, dsn_data: Dict[str, str]) -> bool:
        """
        Establish a database connection.
        
        Args:
            dsn_data: Dictionary with database connection parameters
            
        Returns:
            True if connection successful, False otherwise
        """
        if self.dbconn:
            self.db_disconnect()
        
        try:
            self.dbconn = db.connect(
                host=dsn_data["host"],
                user=dsn_data["user"],
                passwd=dsn_data["password"],
                db=dsn_data["dbname"]
            )
            self.dbconn.autocommit(False)
            self.dbcurs = self.dbconn.cursor()
            self.dbcurs.execute("SET AUTOCOMMIT=0")
            self.dbcurs.execute("SET NAMES 'utf8'")
        except Exception as e:
            log_error(f"Cannot connect to database: {e}")
            return False
        
        return True
    
    def db_commit(self) -> bool:
        """
        Commit current transaction to database.
        
        Returns:
            True if commit successful, False otherwise
        """
        if not self.dbconn:
            log_error("Not connected to database")
            return False
        
        try:
            self.dbconn.commit()
            self.dbcurs.close()
            self.dbcurs = self.dbconn.cursor()
            self.dbcurs.execute("SET AUTOCOMMIT=0")
            self.dbcurs.execute("SET NAMES 'utf8'")
        except Exception as e:
            log_error(f"Cannot commit changes to database: {e}")
            return False
        
        return True
    
    def insert_name(self, ins_data: Tuple[str, str, str]) -> bool:
        """
        Insert a city name into the database.
        
        Args:
            ins_data: Tuple of (city_id, name, name_type)
            
        Returns:
            True if insertion successful, False otherwise
        """
        if not self.dbconn:
            log_error("Not connected to database")
            return False
        
        try:
            self.dbcurs.execute(NAME_INSERT, ins_data)
        except Exception as e:
            log_error(f"Cannot insert city name into database: {e}")
            return False
        
        return True
    
    def insert_city(self, ins_data: List[Any], position: str) -> bool:
        """
        Insert a city location into the database.
        
        Args:
            ins_data: List of city data values
            position: SQL expression for geographic position
            
        Returns:
            True if insertion successful, False otherwise
        """
        if not self.dbconn:
            log_error("Not connected to database")
            return False
        
        try:
            city_insert_str = CITY_INSERT % (position,)
            self.dbcurs.execute(city_insert_str, ins_data)
        except Exception as e:
            log_error(f"Cannot insert city position/info into database: {e}")
            return False
        
        return True
    
    def load_source(self, filename: str) -> int:
        """
        Load city data from a GeoNames source file.
        
        Args:
            filename: Path to the GeoNames data file
            
        Returns:
            0 on success, -1 on error
        """
        city_ids = set()
        
        try:
            with open(filename, 'r', encoding='utf-8') as infile:
                for line_num, line in enumerate(infile, 1):
                    if not line:
                        continue
                    
                    line_list = line.split("\t")
                    if len(line_list) != LINECOLUMNS:
                        log_error(
                            f"Wrong number of columns on line {line_num}: "
                            f"{len(line_list)} instead of {LINECOLUMNS}"
                        )
                        continue
                    
                    if line_list[0].startswith("#"):
                        continue
                    
                    city_id = str(line_list[LCOL_ID])
                    if city_id in city_ids:
                        log_error(f"City ID already used: {city_id} (line {line_num})")
                        continue
                    city_ids.add(city_id)
                    
                    # Extract city names
                    city_name_main = line_list[LCOL_MAIN].strip()
                    city_name_ascii = line_list[LCOL_ASCII].strip()
                    city_name_other = line_list[LCOL_OTHER].split(",")
                    
                    # Extract city attributes
                    city_type = line_list[LCOL_FCODE]
                    city_lat = line_list[LCOL_LAT]
                    city_lon = line_list[LCOL_LON]
                    
                    # Handle elevation
                    city_ele = line_list[LCOL_ELE]
                    if not city_ele:
                        city_ele = line_list[LCOL_ELEAVG]
                    if not city_ele:
                        city_ele = None
                    
                    city_pop = line_list[LCOL_POP]
                    city_cc = line_list[LCOL_CC]
                    city_tz = line_list[LCOL_TZONE]
                    
                    # Create position expression
                    city_pos = f"PointFromText('POINT({city_lat} {city_lon})')"
                    
                    # Insert city data
                    city_data = [city_id, city_type, city_pop, city_ele, city_cc, city_tz]
                    if not self.insert_city(city_data, city_pos):
                        return -1
                    
                    # Insert city names
                    used_names = set()
                    for name, name_type in [(city_name_main, "main"), (city_name_ascii, "ascii")]:
                        if name in used_names or not name:
                            continue
                        used_names.add(name)
                        if not self.insert_name((city_id, name, name_type)):
                            return -1
                    
                    # Insert alternate names
                    for alt_name in city_name_other:
                        alt_name = alt_name.strip()
                        if alt_name in used_names or not alt_name:
                            continue
                        used_names.add(alt_name)
                        if not self.insert_name((city_id, alt_name, "other")):
                            return -1
                
                self.db_commit()
        except IOError as e:
            log_error(f"Cannot open cities file: {e}")
            return -1
        
        return 0


def main() -> int:
    """
    Main entry point for the script.
    
    Returns:
        Exit code (0 for success, 1 for error)
    """
    loader = GeoNamesLoader()
    
    try:
        if not loader.db_connect(cs_dsn):
            return 1
        
        result = loader.load_source(geo_file_name)
        
        if result != 0:
            return 1
        
        return 0
    finally:
        loader.db_disconnect()


if __name__ == '__main__':
    exit(main())


