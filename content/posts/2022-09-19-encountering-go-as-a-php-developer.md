--- 
title: Encountering Go as a PHP developer
categories: [php,go]
date: 2022-09-19
lastmod: 2022-09-21 23:50:00
toc: false
aliases:
  - /blog/2022/09/19/encountering-go-as-php-a-developer/
changelog:
  - corrected STDERR to STDOUT
  - made code examples more realistic
  - various corrections
---
One morning I sat down at my laptop and looked at a Go service, written 2
months previously, that represented my first attempt with the language. When I
wrote it I had been unsure about everything, questioning each line and
frequently googling things like "how to do a foreach in Go". In just two
months I was now able to look at this code and immediately see several ways in
which it could be improved. I now had opinions and felt confident.

I had been a PHP developer for around 14 years. At least 99.9% of the code
I've written has been PHP, my coding experience and skill set has evolved
through PHP and it's community. At this stage in my career I consider myself
to be a relatively good architect, I have a mature understanding of object
oriented programming and it's patterns. 

So why did I start writing Go code?

I was the technical lead of a project at [Inviqa](https://inviqa.com) and the
project had a microservice architecture. I tended to imagine that
microservices should be _fast_ and _self contained_.

PHP was an option, libraries such as
[Amphp](https://amphp.org/) or [ReactPhp](https://reactphp.org/) allow you to
write long running processes, but PHP is not designed with this use case and
elsewhere in Inviqa we had already started using Go.

I knew I was taking a risk, our team didn't have any Go experience, even if
people in the wider company did. But I also wanted to use the best tool for
the job. In the end we decided to write the services in Go and haven't
regretted it.

This blog post (originally written in January 2021) is an attempt to give my
first impressions of the language and to map familiar concepts from PHP to Go.

Culture Shock
-------------

For 10 years the importance of descriptive naming has been impressed on me.
Using `$customer` instead of `$c` for example, or `register(Customer
$customer)` instead of `save($c)`. So I was a bit shocked to see that short
variable names are idiomatic in Go:

```go
func appendSorted(es []muxEntry, e muxEntry) []muxEntry {
	n := len(es)
	i := sort.Search(n, func(i int) bool {
		return len(es[i].pattern) < len(e.pattern)
	})
	if i == n {
		return append(es, e)
	}
	es = append(es, muxEntry{})
	copy(es[i+1:], es[i:])
	es[i] = e
	return es
}
```
_Selected example from the Go HTTP package_

My first impression was 🤢 and then I grew to embrace it, but then I would get
confused and start using meaningful names again and my code was an
inconistent mix of short and long names.

I guess the implication is that you write code that is succinct and that if
you are confused it's because your code is confusing and should be refactored.
But this argument applies equally to PHP so the trade offs are the same. 

This is perhaps one aspect of the general culture of minimalism in Go. The Go
language itself is very minimal and doesn't have many functions you would
expect to find in other languages. This is both a strength and a weakness as
you either writing everything verbosely, or create your own library for each
microservice or you end up using any number of publically available packages,
any one of which could be abandonned.

> "I don't think one letter variables are that popular as they make it seem to
> be. The idea is to have the more verbose variable name the longer it lives,
> so one or two liners can have one-letter variables indeed. Exceptions could
> be indexes in for loops, or reference to the struct we're working with, which
> in PHP would be `$this`" - **@SirRFI** _via. Symfony Slack_

Read more:

- [Hacker News on short names in Go](https://news.ycombinator.com/item?id=16564965)
- [Reddit on naming conventions](https://www.reddit.com/r/golang/comments/j7o96q/variable_naming_conventions_in_go/)

Executing Code
--------------

Let's start with a comparison of "Hello World", in PHP:

```php
<?php
// test.php

echo "Hello World";
```

We execute it as:

```bash
$ php test.php
Hello World
```

In Go:

```go
// hello.go
package main

import (
	"fmt"
)

func main() {
    fmt.Print("Hello World")
}
```

It is executed with `go run`:

```bash
$ go run hello.go
Hello World
```

Here we use the standard `fmt` [package](#namespaces-vs.-packages) which we'll
see more of shortly.

Var Dump and Die
----------------

When learning a new language the first thing I want to do is be able to
`var_dump`, `console.log` or whatever it takes to know:

- The my code is being executed.
- The value of a variable, method call, etc.

In PHP I use `var_dump` (or [posh
variants](https://symfony.com/doc/current/components/var_dumper.html)). In Go
the native equivalent is `fmt.Printf("%#v", value)`:

```go
package main

import (
    "fmt"
)

type Command struct {
    Name string;
    Args []string;
}

func main() {
    cmd := Command{
        Name: "greet",
        Args: []string{"Daniel"},
    }

    fmt.Printf("%#v", cmd) // print the value
}
```

This will print a representation of the value to `STDOUT`:

```bash
$ go run test.go
main.Command{Name:"greet", Args:[]string{"Daniel"}}
```

If you want to "var dump and die":

```go
// ...
panic(fmt.Sprintf("%#v", cmd))
```

Note that we use `Sprintf` (which is [analagous](https://pkg.go.dev/fmt) to
PHP `sprintf`). It returns a formatted string, while `Printf` will send it to
`STDOUT`.

Recently I started using [Spew](https://github.com/davecgh/go-spew) which is
to `fmt.Sprintf("%v")` what `dump(...)` is to `var_dump(...);`.

Composer vs. Go Modules
-----------------------

Go does not have a third-party "package" manager - this functionality is built
in. What we call *packages* in PHP are known as *modules* in Go (packages are
something else - see the following [packages](#namespaces-vs-packages) section).

To require a new module use `go get`

```bash
$ go get github.com/stretchr/testify
```

This will automatically add an entry to `go.mod` (which is like
`composer.json`) and update `go.sum` (which is like `composer.lock`).

Notable differences:

- There is no package registry: you reference the source code repository
  directly.
- You can use two or more _major versions_ of a package concurrently.

Read more about [using go modules](https://go.dev/blog/using-go-modules).

Namespaces vs. Packages
-----------------------

In PHP we organize code with _namespaces_. Go has _packages_.

Like namespaces, packages in Go help you to organize your code. Each directory
within your Go repository can contain one package only.

Since the time of Composer we have adopted the
[PSR-0/4](https://www.php-fig.org/psr/psr-4/) autoloading conventions. As Go
is a compiled language it does not need to "guess" where source files may be,
so autoloading is not required and a package name need not correspond to it's
directory name.

The following source file in PHP:

```php
<php
// src/Handler/InvoiceHandler.php

namespace MyProject\\Handler;

class InvoiceHandler {
}
```

Might look like this in Go:

```go
// handler/invoice_handler.go
package handler

type InvoiceHandler struct {
}
```

The `main` package is special and we'll talk about this next.

Main
----

In PHP you can execute any script:

```php
<?php 

echo "Hello World!";
```

but the following will **not** work with Go:

```go
package hello

import (
	"fmt"
)

fmt.Print("Hello World")
```

```bash
go run test.go                                                                                            ✘ 1 
go run: cannot run non-main package
```

Only the `main` package can be executed, let's fix it:

```go
package main

import (
	"fmt"
)

fmt.Print("Hello World")
```

But it still doesn't work:

```go
go run test.go                                                                                            ✘ 2 
# command-line-arguments
./test.go:7:1: syntax error: non-declaration statement outside function body
```

Unlike PHP we cannot call functions wherever we like, they must be called from
another function. You may have noticed in the previous examples that we always
put our code in the `main()` function. The `go run` command will run the
`main` function inside of the `main` package.

```go
package main

import (
	"fmt"
)

func main() {
    fmt.Print("Hello World")
}
```

Read more:

- [Main and Init functions in Golang](https://www.geeksforgeeks.org/main-and-init-function-in-golang/)

Use vs. Import
--------------

In PHP we import classes, functions and constants using `use`:

```php
<?php

use MyProject\Handler\PostHandler;
use function Amp\call;
use const MyProject\FOOBAR;
```

We can either import the fully-qualified name and use it (e.g. `echo FOOBAR`)
or we use it relative to an imported namespace: `use MyProject;` and `echo MyProject\\FOOBAR`.

In Go we import _packages_:

```go
import (
	"github.com/imdario/mergo"
)

func main() {
    mergo.Merge(/** ... */)
}
```

Above we call the _function_ `Merge` from the
[mergo](https://github.com/imdario/mergo) package. Note that we do not import
specific definitions, we can only import packages.

Like PHP you can also alias imports:

```go
import (
	m "github.com/imdario/mergo"
)

func main() {
    m.Merge(/** ... */)
}
```

> You can also import packages into the current namepsace using the `.` alias
> and avoid referencing the package when using it's definitions, but this
> probably isn't a great idea due to the potential for conflicts.

Read more:

- [Almost everything about imports](https://scene-si.org/2018/01/25/go-tips-and-tricks-almost-everything-about-imports/)

Classes vs. Structs
-------------------

Go is not an object-oriented language but a huge amount of knowledge from that
domain can be transferred to Go. The concept of `class` can be roughly mapped to
`struct`. Unlike classes structs are simply data structures, but they are data
structures to which you can associate methods as we will see later.

This PHP class:

```php
<?php

class Pet {
    public string $name;
    public string $species;
    public int $age;
}
```

could be represented in Go as:

```go
type Pet struct {
    Name string;
    Species string;
    Age int;
}
```

> Note that capitalisation of the fields - in Go capitalisation is used to
> determine the (package level) visiblity of the fields, see
> [visiblity](#visibility)

In PHP methods are defined within the `class` definition. In Go they are
attached _outside_ of the `struct` definition (more on this in the
[methods](#methods) section).

Structs can be "instantiated":

```go
pet := Pet{
    Name: "Thor",
    Species: "Hamster",
    Age: 5,
}
```

In PHP we have constructors (`public function __construct(string $one, string
$two) {}`) which enable us to use the provided constructor arguments in any
way we choose (normally we assign them to properties).

Structs do not have this mechanism. There are no constructors in Go. Instead
it is typical to create "constructor functions":

```go
func NewPet(string name, string species, age int) Pet {
    return Pet{
        Name: name,
        Species: species,
        Age: age,
    }
}

// and use it as follows
fmt.Printf("%#v", NewPet("Thor", "Hamster", 5))
```

Read more:

- [Effective Go / Constructors](https://golang.org/doc/effective_go#composite_literals)

Methods
-------

As previously mentioned a `struct` loosely corresponds to a `class`. A `struct` can have
methods associated with it, but unlike PHP, you can associate methods on
any type as long as it is [defined in the same
package](https://go.dev/tour/methods/3).

In PHP a class may look like this:

```php
<?php

class Pet {
    private bool $vaccinated = false;

    public function isVaccinated(): void {
        return $this->vaccinated;
    }

    public function vaccinate(): void {
        $this->vaccinated = true;
    }
}
```

In Go this might look something like:

```go
type Pet struct {
    vaccinated bool;
}

func (p Pet) IsVaccinated() bool {
    return p.vaccinated
}

func (p *Pet) Vaccinate() {
    p.vaccinated = true
}

```

Notice that the first method is defined with a _reciever_ (`p Pet`).
This reciever indicates to which type the method should be bound. The
reciever name maps to the concept of `$this` in PHP.

The second method uses a pointer reciever, we know it's a pointer because the
type is prefixed with `*`. This effectively means that we can _mutate_ the
fields of the struct to which the method is attached - more on this
[later](#pointers-vs-pass-by-reference)

If your method only needs to read the field, then it makes sense to use a
_value receiver_. The first method uses a value reciever: `(p Pet)`.

The public (see [visiblity](#visibility)) `Vaccinate` method can be called as follows:

```go
pet := Pet{}
pet.Vaccinate()
```

Read more:

- [Go101 / Methods in Go](https://go101.org/article/method.html)

Interfaces
----------

Go has interfaces, but unlike PHP they are not _explicitly_ implemented (there
is no `implements` keyword). Rather they define the "methods" that a struct
needs in order to be accepted as an argument.

```go
type user interface {
	name() string
}
```

Above we define a user interface (with a lowercase `u` meaning it's
private and available only to the current package). We can depend on an
interface:

```go
func hello(u user) {
    fmt.Printf("Hello %s", u.name())
}
```

This enables _any_ struct to be passed as long as it exactly implements the
`name` method.

Compared to PHP this is arguably more flexible. Each package can define what
it needs and it doesn't care how you supply them. On the other hand it makes
refactoring more difficult, as implementations don't know they are
implementations until they are used.

`interface{}` is also a type which can be used to indicate "any value"
(similar to `mixed` in PHP). We will see more about this later.

> since Go 1.18 you can also use `any` to indicate any value (it's an alias to
> `interface{}`)

Read more:

- [Introduction to Interfaces](https://jordanorelli.com/post/32665860244/how-to-use-interfaces-in-go)

Visiblity
---------

In PHP, class-member visiblity is determined by the `private`, `protected` and
`public` keywords:

```php
<?php

class Example {
    public int $one;
    protected int $two;
    private int $three;
}
```

In Go visiblity is NOT indicated by a keyword but by the case of the first
character of the field name:

```go
type Example struct {
    One string;   // public
    three string; // private
}
```

Above `One` is public while `three` is private to the current _package_ (the
concept of `protected` does not exist as one `struct` cannot inherit from
another).

> Private fields are not private to the struct which defined them, but rather
> private to the entire package in which the struct is defined.

This rule applies to any definition:

```go
// functions
func ThisFuncIsPublic() {
}
func thisFuncIsPrivate() {
}

// constants
const ThisConstantIsPublic = "yes"
const thisConstantIsPrivate = "yes"

var ThisIsPublicVariable = "yes"
var thisIsPrivateVariable = "yes"
```

So, unlike PHP, packages can _conceal_ definitions from other packages and
strictly expose public APIs.

NULL vs. Nil
------------

The Go concept of `Nil` roughly corresponds to the `null` in PHP but differs
in some important aspects.

`Nil` represents the "empty" value of various, but not all, types.

Unlike PHP there is no concept of "nullability" and there are no union types.
If you declare a field to be `string` then it has to be a string, if no value
is specified the "empty" value will be used (in this case an empty string).

While a string _value_ cannot be `Nil`, a _pointer_ to a string value can be
`Nil`. This is because the default value of a pointer value is `Nil`.

There are various different types of `Nil` as they differ based on the type
they are the empty value for: `Nil` for a pointer cannot be compared with
`Nil` for a `map` for example.

```php
<?php

class Foo {
    public ?string $bar = null;
}
var_dump(new Foo()); // bar is NULL
```

```go
type Foo struct {
    Bar string
}
fmt.Printf("%#v", Foo{}) // Bar is empty string
```

> If you are using a relational database you will likely soon encounter the
> **joy** of mapping [nullable database fields to structs in Go](https://ente.io/blog/tech/go-nulls-and-sql/)

Read more:

- [Nils in Go](https://go101.org/article/nil.html)

Errors and Exceptions
---------------------

In PHP we would typically handle an error by throwing an exception, the
exeption will _bubble up_ through the call stack and can be _handled_ with a
try/catch.

In Go the concept of exceptions maps to the concept of panic/recover but
it is common to explicitly return errors:

```go
func GetUser(name string) (User, error) {
    if name == "Alice" {
         return User{Name: "Alice"}, nil
    }

    return User{}, errors.New("Name must be 'Alice'")
}
```

Note that we return both a value and an error type. If the happy path is
followed (the name is "Alice") then we return a populated `User` struct
and `nil` as the error, in the error case we return an _empty_ `User` and
a non-nil error. 

The call is handled as follows:

```go
something, err := GetUser("Bob")

if err != nil {
    // handle the error
}

// do something with "something"
```

Explicitly returning an error clearly indicates to the consumer of the library
that an error can occur and is expected. It _forces_ the consumer to consider
the handling of the error.

The alternate way of handling an error is to `panic`. Panic maps roughly to
the concept of "Exception". It bubbles-up through the call stack and can be
handled via. the `recover` mechanism. Panic should generally be used only when
an _unexpected_ error occcurs. If you have an error that must be handled
further up the call stack, it's idiomatic and wise to return an error, if you
have an error that nothing can handle (e.g. `MySQL connection has gone away`)
then panicing is fine.

> There seems to be a general misconception that panicing will crash your entire
> application, but in reality panics can be "caught" and handled in much the 
> same way exceptions can be. The Go HTTP server will handle panics from
> handlers and return a 500 (not crashing the entire server).

Read more:

- [Working with Errors in Go 1.13](https://go.dev/blog/go1.13-errors)
- [When to Panic](https://levelup.gitconnected.com/its-ok-to-panic-in-go-8169e4e3ce6c)
- [Defer/Panic/Recover](https://go.dev/blog/defer-panic-and-recover)

Pointers vs. Pass-By-Reference
------------------------------

In PHP objects are always passed by reference. If you pass an object to a
function and modify that object not only in the local scope, but in all other
scopes where that object is referenced - because it's an alias to the same
object! 

In contrast non-object values in PHP are passed by _value_ by default, and you
can pass  _by reference_ by type hinting the parameter with `&` (e.g. `function
foobar(&$array) { $array['bar'] = 'baz'; }`).

Go has the concept of pointers which are different to references in that they actually
[reference the memory location](https://go.dev/tour/moretypes/1) where a value is stored.

In the following code example we set the `name` on a `User` struct, but it
does not work you might expect:

```go
package main

import ("fmt")

type User struct {
    Name string;
}

func SetUserName(user User, name string) {
    user.Name = name
}


func main() {
    user := User{}

    SetUserName(user, "Daniel")

    fmt.Printf("%#v", user.Name) // returns empty string
}
```

The user struct is "copied" to the function, which means the function modifies
a different "instance" of the struct. If we use a pointer in the `SetUserName`
function it works on the same "instance":

```go
package main

import ("fmt")

type User struct {
    Name string;
}

func SetUserName(user *User, name string) {
    user.Name = name
}


func main() {
    user := User{}

    SetUserName(&user, "Daniel")

    fmt.Printf("%v", user.Name) // prints "Daniel"
}
```

Unlike PHP Go does not change it's behavior based on the type of value that is
passed. If you pass a struct as a parameter to a function it is passed by
_value_ by default (i.e. it is copied).

Testing
-------

Go has a built-in test _runner_ based on a convention: any file ending with
`_test.go` will be treated as test. It is conventional to place test files
"next" to the files they test:

```text
handler/
    invoice.go
    invoice_test.go
```

We mentioned that it is a test _runner_. Unlike PHPUnit it has no built-in
support for assertions, instead you can use conditionals:

```go
package hello

import (
    "testing"
)

func TestHello(t *testing.T) {
    expected := 3
    result   := AddOne(2)

    if result != expected {
        t.Fatalf(`Expected "%d", got "%d"`, expected, result)
    }
}
```

I personally prefer to use an assertion library such as
[testify](https://github.com/stretchr/testify) in which case the test can be
written as: 

```go
func TestHello(t *testing.T) {
    expected := 3
    result   := AddOne(2)

    require.IsEqual(t, 3, 2)
}
```

You can run tests with:

```bash
$ go test ./handler/invoice_test.go`
```

Or run _all_ tests using:

```bash
$ go test ./...
```

The output is minimal. You can use a third-party tool such as
[gotestsum](https://github.com/gotestyourself/gotestsum#documentation) to
achieve more colorful results.

Collections and Arrays
----------------------

In PHP we have the `array` type which can be either a list or a dictionary. In
Go we have distinct types for lists (as
[arrays](https://go.dev/tour/moretypes/6) and [slices](https://go.dev/tour/moretypes/7)) and
[maps](https://gobyexample.com/maps):

For example:

```php
$foo = [1, 2, 3];
$bar = ['one' => 1, 'two' => 2];
```

could be represented in Go as:

```go
foo := []int{1,2,3} // dynamically sized slice
bar := map[string]int{"one": 1, "two": 2} // map
```

> note that [arrays](https://go.dev/tour/moretypes/6) are fixed size lists,
> e.g. `[3]int` is an "list" of 3 ints

One of the features of Go's type system is type aliasing and being able to
associate methods to structs any type which is defined in the same package, in
the following example we define `UserCollection` as an alias of a "slice" of
users:

```go
type User struct {
    Name string;
}
type UserCollection []User
```

Now we now add methods to this "collection":

```go
func (c UserCollection) ByName() UserCollection {
    // filter by user name and return a new collection
    // return c
}
```

Read more:

- [Go tour on arrays](https://go.dev/tour/moretypes/6)
- [Go tour on slices](https://go.dev/tour/moretypes/7)

Foreach and loop scope
----------------------

In PHP we iterate over arrays and iterables with the `foreach` loop:

```php
foreach ($items as $key => $value) {
}
```

In Go this translates to:

```go
for key, value := range items {
}
```

One nice feature of Go's variable scoping is that the variables `key` and
`value` above are only available within the scope of the `for` loop, they do
not pollute the subsequent code.

Summary
-------

Above I've outlined _some_ of the concepts which I was able to map from PHP. I
am an aspiring [Gopher](https://go.dev/blog/gopher) and am capable of
being productive and applying most of my existing progamming knowledge to Go.

If you haven't already it is strongly encouraged to do the [Go
Tour](https://tour.golang.org/welcome/1) to fill in the blanks. In fact you
should have done that before reading this blog post.

Better learning resources:

- [The Go Tour](https://tour.golang.org/welcome/1)
- [Learn Go with Tests](https://quii.gitbook.io/learn-go-with-tests/)
