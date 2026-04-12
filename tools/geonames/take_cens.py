#!/usr/bin/env python
"""
Country centers calculator from GeoNames data.

Processes GeoNames JSON data and generates JavaScript with country center coordinates.
"""

import json
import sys
from typing import Dict, Any


def calculate_country_center(country_data: Dict[str, Any]) -> Dict[str, float]:
    """
    Calculate the center point of a country from its bounding box.
    
    Args:
        country_data: Dictionary containing country bounding box information
        
    Returns:
        Dictionary with 'lon' and 'lat' keys for longitude and latitude
    """
    b_box_west = country_data['bBoxWest']
    b_box_east = country_data['bBoxEast']
    b_box_north = country_data['bBoxNorth']
    b_box_south = country_data['bBoxSouth']
    
    # Handle countries that cross the antimeridian
    if b_box_west > b_box_east:
        b_box_east += 360
    
    # Calculate center longitude
    center_lon = (b_box_west + b_box_east) / 2.0
    if center_lon > 180:
        center_lon -= 180
    
    # Calculate center latitude
    center_lat = (b_box_north + b_box_south) / 2.0
    
    return {'lon': center_lon, 'lat': center_lat}


def generate_js_output(country_centers: Dict[str, Dict[str, float]]) -> str:
    """
    Generate JavaScript code from country centers data.
    
    Args:
        country_centers: Dictionary mapping country codes to center coordinates
        
    Returns:
        JavaScript code as a string
    """
    lines = ["country_centers = {};"]
    
    for country_code, center in country_centers.items():
        lines.append(
            f"country_centers['{country_code}'] = {{'lon': {center['lon']}, 'lat': {center['lat']}}};"
        )
    
    return "\n".join(lines) + "\n"


def process_geonames_file(input_file: str, output_file: str) -> None:
    """
    Process GeoNames JSON file and generate JavaScript output.
    
    Args:
        input_file: Path to input JSON file
        output_file: Path for output JavaScript file
        
    Raises:
        FileNotFoundError: If input file doesn't exist
        json.JSONDecodeError: If input file is not valid JSON
        KeyError: If expected fields are missing in JSON data
        IOError: If output file can't be written
    """
    # Read and parse JSON input
    with open(input_file, 'r', encoding='utf-8') as f_in:
        json_data = json.load(f_in)
    
    country_centers = {}
    
    # Process each country in the geonames data
    for country in json_data['geonames']:
        country_code = country['countryCode']
        center = calculate_country_center(country)
        country_centers[country_code] = center
    
    # Write JavaScript output
    with open(output_file, 'w', encoding='utf-8') as f_out:
        f_out.write(generate_js_output(country_centers))


def print_usage(script_name: str) -> None:
    """Print usage information."""
    sys.stderr.write(f"Usage: {script_name} infile.json outfile.js\n")
    sys.stderr.write("\nConverts GeoNames JSON data to JavaScript country centers.\n")


def main() -> int:
    """
    Main entry point for the script.
    
    Returns:
        Exit code (0 for success, 1 for error)
    """
    if len(sys.argv) != 3:
        print_usage(sys.argv[0])
        return 1
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    try:
        process_geonames_file(input_file, output_file)
        print(f"Successfully processed {input_file}")
        print(f"Output written to {output_file}")
        return 0
    except FileNotFoundError as e:
        sys.stderr.write(f"Error: Input file not found: {e}\n")
        return 1
    except json.JSONDecodeError as e:
        sys.stderr.write(f"Error: Invalid JSON in input file: {e}\n")
        return 1
    except KeyError as e:
        sys.stderr.write(f"Error: Missing expected field in JSON data: {e}\n")
        return 1
    except IOError as e:
        sys.stderr.write(f"Error: I/O error: {e}\n")
        return 1


if __name__ == '__main__':
    exit(main())


