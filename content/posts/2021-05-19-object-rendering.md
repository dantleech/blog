--- 
title: Object Rendering or MVC the right way
categories: [phpbench,php]
date: 2021-05-19
---

The templates we use in our favourite MVC frameworks are awful. You pass an
unstructured bunch of data to a template which _hopes_ the you passed
everything it needed. They do not define what their inputs are, they are not
type safe.

For some years I've been using a different way of rendering views - it started
when I was trying to create a new CMS framework, but since then it has come in
useful time and time again. I call it `Object Rendering`, but it is very
similar to Martin Fowlers [Presentation
Model](https://www.martinfowler.com/eaaDev/PresentationModel.html).

Object Rendering
----------------

The idea is simple:

- Render a template based on the FQN of an object. 
- That object contains all the data for the template.
- The template can render objects from the main object.

So, assuming we do this in a typical web application your controller might
look like this:

```php
<?php

class MyController {

    public function __construct(private ObjectRenderer $renderer)
    {}

    public function blogPostAction(string $slug): Response
    {
        $view = new BlogPostView(
            $this->blogRepository->findBySlug($slug)
        );

        return new Response(200, $this->objectRenderer->render($view);
    }
}
```

Now without creating any templates the first thing that will happen is you
will get an exception message:

```
Unable to find template at `templates/BlogPostView.twig`
```

Behind the scenes the template paths are mapped based on the FQN prefix:

```php
[
    'MyApp\\View\\' => 'templates'
    'MyApp\\Entity\\' => 'templates/entity'
]
```

Exception Driven Development
----------------------------

So then, let's create a template:

```
<html>
    <head>
        <title>{{ view.title }}</title>
    </head>
    <body>
        {{ render(view.post) }}
    </body>
</html>
```

We call `render(<object>)` to render the `Post` object and we will get an
exception:

```
Unable to find template at `templates/entity/Post.twig`
```

Great! Let's create that:

```
<article>
    <h2>{{ view.title }}</h2>
    <div>
        {{ view.body }}
    </div>
    {% for tag in view.tags %}{{ render(tag) }}{% endfor %}
</article>
```

Note that the template always has exactly one parameter: `view`. Can you guess
what happens next?

```
Unable to find template at `templates/entity/Tag.twig`
```

And so on. I like this - it tells you what it needs and is easy to reason
about - you have an object? render it. This creates very strong boundaries
around templates - preventing them from getting to greedy.

Usually when I implement this pattern I think - oh! I need to pass extra
parameters to the template:

```php
$blogPost = $repository->find('my-blog-post');
$renderer->render($blogPost, [
   'next' => 'https://www....',
   'previous' => 'https://www....',
]);
```

But no! This is not needed! We need actually to create a new object containing
those parameters:

```php
$renderer->render(
    new BlogPostView(
        $blogPost,
        new Link('next'),
        new Link('previous')
    )
);
```

This is awesome in a subtle way - we can put all that behavior which may have
ended up in the template into an *object* and we can benefit from all the nice
things that implies like testing and static analysis.

The Root of All Evil
--------------------

But the best thing is _inheritence_ (hear me out, it's might be fine).

Let's say we `Post` extends `Article` extends `Content`. The object renderer
will automatically fall back to the parent class if the top class does not
exist.

Because templates map exactly to a certain class FQN, they are guaranteed to work
with any objects extending that class.

So for example, if we are rendering a list:

```
$list = new List([
    new Header([
        new StringCell('ID'),
        new StringCell('Title'),
        new StringCell('Last Modified'),
        new StringCell('Action'),
    ]),
    new ListBody([
        new ListRow(
            new IntegerCell(5),
            new StringCell('Foobar'),
            new DateCell(new DateTime('05-05-2020')),
            new LinkCell('Edit', 'https://example.com/edit/this')
        ),
        // ...
    ])
);
echo $renderer->render($list);
```

Imagine `StringCell` and `IntegerCell` nodes extend `ScalarCell`, then we
would need only to create `ScalarCell` to render both. Later when we add
`FloatCell` it wil just work, while providing us a way to specialize.

Also, by running our application we would effectively be prompted to create each
successive template (when we stick to the rule of calling the renderer for all
objects). 

Into The Wild
-------------

I've used this so far on
[phpactor](https://github.com/phpactor/phpactor) to render [markdown help](https://github.com/phpactor/language-server-phpactor-extensions/tree/master/templates/markdown/Phpactor) for code reflection elements (e.g. rendering method / class / type information).
tu
