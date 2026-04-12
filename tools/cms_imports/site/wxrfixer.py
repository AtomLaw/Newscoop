#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
WXR (WordPress eXtended RSS) file fixer.

Fixes invalid WXR files, particularly issues with embedded CDATA sections.
Usage: wxrfixer.py input.file output.file
"""

import re
import sys
from typing import Optional


class WXR_Fixer:
    """Handles fixing of WordPress WXR export files."""
    
    # Regex patterns for problematic CDATA sections
    js_cdata = re.compile(r"]]>[\s]*</script>", re.UNICODE)
    css_cdata = re.compile(r"]]>[\s]*\*/[\s]*</style>", re.UNICODE)
    amp_cdata = re.compile(r"& ", re.UNICODE)
    
    # Replacement strings
    js_subst = "]]]]><![CDATA[>\n</script>"
    css_subst = "]]]]><![CDATA[>*/\n</style>"
    amp_subst = "&amp; "
    
    def write_error(self, msg: str) -> None:
        """
        Write an error message to stderr.
        
        Args:
            msg: Error message to write
        """
        if not msg.endswith("\n"):
            msg += "\n"
        try:
            sys.stderr.write(msg)
        except Exception:
            pass
    
    def try_fix_wp(self, infile_name: str, outfile_name: str) -> bool:
        """
        Attempt to fix WordPress WXR file issues.
        
        Args:
            infile_name: Path to input WXR file
            outfile_name: Path for output fixed file
            
        Returns:
            True if processing completed, False on error
        """
        infile = None
        outfile = None
        
        try:
            infile = open(infile_name, "r", encoding='utf-8')
        except Exception as exc:
            self.write_error(f"Cannot open '{infile_name}' for read: {exc}")
            return False
        
        try:
            outfile = open(outfile_name, "w", encoding='utf-8')
        except Exception as exc:
            self.write_error(f"Cannot open '{outfile_name}' for write: {exc}")
            infile.close()
            return False
        
        last_line: Optional[str] = None
        
        try:
            # Process lines, checking pairs for CDATA issues
            for line in infile:
                # Fix ampersand issues
                line = re.sub(self.amp_cdata, self.amp_subst, line)
                
                if last_line:
                    double_line = last_line + line
                    
                    # Check for JavaScript CDATA issues
                    checked_dl = re.sub(self.js_cdata, self.js_subst, double_line)
                    if double_line != checked_dl:
                        outfile.write(checked_dl)
                        last_line = None
                        continue
                    
                    # Check for CSS CDATA issues
                    checked_dl = re.sub(self.css_cdata, self.css_subst, double_line)
                    if double_line != checked_dl:
                        outfile.write(checked_dl)
                        last_line = None
                        continue
                    
                    # No issues found, write the previous line
                    outfile.write(last_line)
                
                last_line = line
            
            # Handle any remaining last line
            if last_line:
                to_use_last = True
                
                if to_use_last:
                    checked_line = re.sub(self.js_cdata, self.js_subst, last_line)
                    if checked_line != last_line:
                        outfile.write(checked_line)
                        to_use_last = False
                
                if to_use_last:
                    checked_line = re.sub(self.css_cdata, self.css_subst, last_line)
                    if checked_line != last_line:
                        outfile.write(checked_line)
                        to_use_last = False
                
                if to_use_last:
                    outfile.write(last_line)
        
        except Exception as exc:
            self.write_error(f"Error during XML checking: {exc}")
        
        finally:
            outfile.close()
            infile.close()
        
        return True


def main() -> int:
    """
    Main entry point for the script.
    
    Returns:
        Exit code (0 for success, 1 for error)
    """
    fixer = WXR_Fixer()
    
    if len(sys.argv) < 3:
        fixer.write_error(f"Usage: {sys.argv[0]} input.file output.file")
        fixer.write_error("\nFixes invalid WXR (WordPress eXtended RSS) files.")
        return 1
    
    infile_name = sys.argv[1]
    outfile_name = sys.argv[2]
    
    if fixer.try_fix_wp(infile_name, outfile_name):
        return 0
    else:
        return 1


if __name__ == '__main__':
    exit(main())


