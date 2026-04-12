#!/usr/bin/env python
"""
Country codes converter script.

Converts a tab-separated country codes file into PHP and JavaScript formats.
"""

import os
from typing import List, Tuple

# Default file paths
IN_FILE = 'country_codes.txt'
OUT_FILE_PHP = 'country_codes.php'
OUT_FILE_JS = 'country_codes.js'


def escape_single_quotes(text: str) -> str:
    """Escape single quotes in a string for use in code."""
    return text.replace("'", "\\'")


def parse_country_line(line: str) -> Tuple[str, str]:
    """
    Parse a single line from the country codes file.
    
    Args:
        line: A tab-separated line with country name and code
        
    Returns:
        Tuple of (country_name, country_code)
        
    Raises:
        ValueError: If line format is invalid
    """
    parts = line.split("\t")
    if len(parts) < 2:
        raise ValueError(f"Invalid line format: expected at least 2 tab-separated values, got {len(parts)}")
    
    # Replace '!' with '/' and escape single quotes
    country_name = parts[0].replace('!', '/').strip()
    country_code = parts[1].strip()
    
    return country_name, country_code


def generate_php_output(countries: List[Tuple[str, str]]) -> str:
    """
    Generate PHP array output from country data.
    
    Args:
        countries: List of (name, code) tuples
        
    Returns:
        PHP code as a string
    """
    lines = ["<?php", "$country_codes_alpha_2 = array("]
    
    for name, code in countries:
        escaped_name = escape_single_quotes(name)
        escaped_code = escape_single_quotes(code)
        lines.append(f"'{escaped_name}' => '{escaped_code}',")
    
    lines.extend([");", "?>"])
    
    return "\n".join(lines) + "\n"


def generate_js_output(countries: List[Tuple[str, str]]) -> str:
    """
    Generate JavaScript arrays from country data.
    
    Args:
        countries: List of (name, code) tuples
        
    Returns:
        JavaScript code as a string
    """
    lines = ["var country_codes_alpha_2 = [];"]
    
    # Generate array push statements
    for name, code in countries:
        escaped_code = escape_single_quotes(code)
        escaped_name = escape_single_quotes(name)
        lines.append(f"country_codes_alpha_2.push('{escaped_code}');")
    
    # Generate country mapping object
    lines.append("var country_codes_alpha_2_countries = {};")
    for name, code in countries:
        escaped_code = escape_single_quotes(code)
        escaped_name = escape_single_quotes(name)
        lines.append(f"country_codes_alpha_2_countries['{escaped_code}'] = '{escaped_name}';")
    
    return "\n".join(lines) + "\n"


def convert_country_codes(input_file: str, php_output: str, js_output: str) -> None:
    """
    Convert country codes from input file to PHP and JavaScript formats.
    
    Args:
        input_file: Path to input file with country codes
        php_output: Path for PHP output file
        js_output: Path for JavaScript output file
        
    Raises:
        FileNotFoundError: If input file doesn't exist
        IOError: If output files can't be written
    """
    countries = []
    
    # Read and parse input file
    with open(input_file, 'r', encoding='utf-8') as infile:
        for line_num, line in enumerate(infile, 1):
            line = line.strip()
            
            # Skip empty lines and comments
            if not line or line.startswith("#"):
                continue
            
            try:
                country_data = parse_country_line(line)
                countries.append(country_data)
            except ValueError as e:
                print(f"Warning: Skipping line {line_num}: {e}")
    
    # Write PHP output
    with open(php_output, 'w', encoding='utf-8') as outfile:
        outfile.write(generate_php_output(countries))
    
    # Write JavaScript output
    with open(js_output, 'w', encoding='utf-8') as outfile:
        outfile.write(generate_js_output(countries))


def main():
    """Main entry point for the script."""
    # Check if input file exists
    if not os.path.exists(IN_FILE):
        print(f"Error: Input file '{IN_FILE}' not found.")
        print("Please create a tab-separated file with country names and codes.")
        return 1
    
    try:
        convert_country_codes(IN_FILE, OUT_FILE_PHP, OUT_FILE_JS)
        print(f"Successfully converted {IN_FILE}")
        print(f"  - PHP output: {OUT_FILE_PHP}")
        print(f"  - JavaScript output: {OUT_FILE_JS}")
        return 0
    except IOError as e:
        print(f"Error: {e}")
        return 1


if __name__ == '__main__':
    exit(main())

