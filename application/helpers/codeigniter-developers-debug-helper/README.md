DATETIME of last agent review: 24/09/2025 12:48

CodeIgniter Developers Debug Helper
===================================

The helper at `application/helpers/codeigniter-developers-debug-helper/vayes_helper.php` provides the `vdebug()` function for inspecting variables during development in both web and CLI contexts.

Usage
-----

```
vdebug($data, $die = false, $add_var_dump = false, $add_last_query = true);
```

Features
--------

- Renders a CodeIgniter styled HTML panel containing the expression name and detected type.
- Dumps `print_r` output and optionally includes `var_dump` output when `$add_var_dump` is true.
- Includes `$CI->db->last_query()` when available and `$add_last_query` is true.
- Exits immediately when `$die` is true; otherwise execution continues.
- Emits plain text when invoked from the CLI for easier terminal reading.

Load the helper with `$this->load->helper('vayes');` before calling `vdebug()`.
