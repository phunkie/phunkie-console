# Phunkie Console

A powerful REPL (Read-Eval-Print Loop) for [Phunkie](https://github.com/phunkie/phunkie) development, providing an interactive environment to explore functional programming concepts in PHP.

## What is Phunkie Console?

Phunkie Console is an interactive development environment that brings the power of functional programming to PHP through the Phunkie library. It provides a REPL interface where you can experiment with functional data types, monads, functors, and other category theory concepts in real-time.

## Features

### Interactive REPL
- **Real-time evaluation** of PHP expressions and functional code
- **Type inference** with automatic type detection and display
- **Colored output** for better readability (can be disabled with `--no-colors`)
- **Error handling** with detailed error messages and stack traces

### Functional Data Types Support
- **Options**: `Some(42)`, `None()`
- **Lists**: `ImmList(1, 2, 3)`
- **Scalars**: Integers, Doubles, Strings, Booleans, Null
- **Custom types**: Support for any Phunkie data type

### Built-in Commands
- `:help`, `:h`, `:?` - Display available commands
- `:import <module>` - Import Phunkie function modules
- `:type <expr>`, `:t` - Display the type of an expression
- `:kind <type>`, `:k` - Display the kind of a type
- `:load <file>`, `:l` - Load a PHP file
- `:exit` - Exit the console

### Multiple Execution Modes
- **Interactive mode**: Full REPL experience (default)
- **Single-line mode**: Execute one expression and exit (`-r` flag)
- **File mode**: Execute a PHP file
- **Help mode**: Display usage information

## Installation

```bash
# Clone the repository
git clone https://github.com/phunkie/phunkie-console.git
cd phunkie-console

# Install dependencies
composer install

# Make the binary executable
chmod +x bin/phunkie
```

## Usage

### Interactive Mode (Default)
```bash
$ bin/phunkie
Welcome to phunkie console.

Type in expressions to have them evaluated.

phunkie > 42
$var0: Int = 42

phunkie > Some(42)
$var0: Option<Int> = Some(42)

phunkie > ImmList(1, 2, 3)
$var0: List<Int> = List(1, 2, 3)

phunkie > :import option/map
imported function \Phunkie\Functions\option\map()

phunkie > map(fn($x) => $x * 2, Some(21))
$var0: Option<Int> = Some(42)
```

### Single-line Mode
```bash
$ bin/phunkie -r "1 + 1"
$var0: Int = 2

$ bin/phunkie -r "Some(42)"
$var0: Option<Int> = Some(42)
```

### File Execution
```bash
$ bin/phunkie my_script.php
```

### Disable Colors
```bash
$ bin/phunkie --no-colors
```

## Examples

### Working with Options
```php
phunkie > Some(42)
$var0: Option<Int> = Some(42)

phunkie > None()
$var0: None = None

phunkie > Some(None())
$var0: Option<None> = Some(None)
```

### Working with Lists
```php
phunkie > ImmList(1, 2, 3)
$var0: List<Int> = List(1, 2, 3)

phunkie > ImmList(Some(1), Some(2), Some(3))
$var0: List<Option<Int>> = List(Some(1), Some(2), Some(3))
```

### Function Declarations
```php
phunkie > function double($x) { return $x * 2; }
defined function double

phunkie > double(21)
$var0: Int = 42
```

### Importing Phunkie Functions
```php
phunkie > :import option/map
imported function \Phunkie\Functions\option\map()

phunkie > :import list/foldLeft
imported function \Phunkie\Functions\list\foldLeft()

phunkie > map(fn($x) => $x * 2, Some(21))
$var0: Option<Int> = Some(42)
```

## Requirements

- PHP 8.1 or higher
- Composer
- Phunkie library (automatically installed as dependency)

## Development

### Running Tests
```bash
# Run Behat tests
vendor/bin/behat

# Run PHPSpec tests
vendor/bin/phpspec run
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built on top of the [Phunkie](https://github.com/phunkie/phunkie) functional programming library
- Inspired by Scala's REPL and functional programming concepts
- Uses [PHP-Parser](https://github.com/nikic/PHP-Parser) for code parsing and evaluation

```bash
$ bin/phunkie-console
Welcome to phunkie console.

Type in expressions to have them evaluated.

phunkie >
```

