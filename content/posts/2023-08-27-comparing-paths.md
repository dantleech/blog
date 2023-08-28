--- 
title: "Comparing Paths"
categories: [strava]
date: 2023-08-27
draft: false
---

(this post is a WIP)

[Strava](https://www.strava.com) is a service that allows you to record,
analyze and share your sporting activities.  [Strava RS](https://github.com/dantleech/strava-rs) is a TUI (terminal user
interface) client for Strava that I'm working on. It provides an offline,
keyboard driven, interface to Strava using data collected fro the [Strava
API](https://developers.strava.com/docs/reference/).

One of the feature the Strava app provides is "Matched Routes": if
the activity you performed _closely matches_ the route of any previous
activities, you can list and compare your previous performances.

In Strava this is a premium feature. I wanted to implement this feature in
Strava RS, which essentially boils down to: **How similar is route A to route
B**?

I had no idea how to answer this question so, after googling and not finding
anything was applicable (or if it was, I didn't understand), I decided to
figure it out by myself.

> **disclaimer**: there may well be better ways to solve this problem, my
> solution will be blindingly obvious to some, it's possibly also wrong. But
> hey, I got a new graphics tablet and wanted to try it out.

## The Problem

Strava provides the routes as a series of
[longitude/latitude](https://en.wikipedia.org/wiki/Geographic_coordinate_system) coordinates encoded using the [Google Polyline Algorithm](https://developers.google.com/maps/documentation/utilities/polylinealgorithm). Once decoded you have:

```text
-2.44698, 50.62649
-2.44728, 50.62669
-2.44753, 50.62678
-2.44774, 50.6269
-2.44798, 50.62694
-2.44844, 50.62707
...
```

The full series would be plotted like this in Strava RS (actually the
[ParkRun](https://www.parkrun.org.uk/weymouth/) in Weymouth)

```text
⠈⢣                   
 ⢸⡀                  
  ⠑⡆                 
   ⠘⠦⣀⣀⡀             
     ⠈⠁⠱⡀   ⢀⣀       
        ⢣ ⡠⠊⠉⠉⡇      
         ⠉    ⡇      
              ⢣      
              ⣎⠖⢄    
              ⡇ ⠈⢲⡀  
              ⠧⡀  ⠑⢦ 
               ⠈⠑⢤⡀ ⡳
                  ⠉⠢⡟
```

Now, I want to compare my times for all attempts I made on this route. It's
inevitable that the exact set of coordinates for each attempt will deviate
slightly.

{{< image "/images/2023-08-27/routes1.png" Resize "700x" "Three similar and one dissimilar routes" >}}

Above we can clearly see that routes 1, 2 and 4 are the same shapes, and that
3 is clearly different. In this case 3 is actually the
[Dorchester](https://www.parkrun.org.uk/thegreatfield/) ParkRun:

{{< image "/images/2023-08-27/route3.png" Resize "400x" "Weymouth and Dorchester are 5 miles apart" >}}

Route 3 doesn't intersect with our run in Weymouth. Let's overlay the 3
Weymouth Parkruns:

{{< image "/images/2023-08-27/routes2.png" Resize  "500x" "Three similar routes overlaid on top of one another" >}}

As a human we can clearly see they are the same:

- They share the same geographical area.
- The paths are very close together.

But how to get a computer to recognise this? We can calculate the distance
between the points.

## Similarity Score

{{< image "/images/2023-08-27/routes4.png" Resize "500x" "Comparing two similar routes" >}}

Adding all the distances gives a score, in this case `0.76` and we can say that
the black route has a **similarity score** of `0.76` against the green route.

{{< image "/images/2023-08-27/routes5.png" Resize "500x" "Dorchester and Weymouth are not similar" >}}

Dorchester and Weymouth are clearly not the same route giving a similarity
score of `481`, so we could say that if the similarity score is less than
`1.0` then we have a match! This seems like a good approach to start with.

However, as a **human** I was able to draw some arbitrary lines to geographically
similar points, but unfortunately making the computer do this would be
a whole different problem to solve. How do we determine which points to
compare?

## Normalisation

{{< image "/images/2023-08-27/routes6.png" Resize "550x" "Two similar routes with different number of co-ordinates" >}}

Route A has 20 coordinates and route B has 15 coordinates (vastly simplified).

We can _normalize_ the routes to have the same number of coordinates, we can
then score each coordinate against its counterpart:

{{< image "/images/2023-08-27/routes7.png" Resize "500x" "Normalized routes with same number of co-ordinates" >}}

So we can now summarise the proposal

- **Normalize** the two routes so that they have the same number of coordinates.
- Sum the difference between the opposing coordinates to produce a **similarity score**.
- If the similarity score between two routes is below a given threshold **it's a match**!

Now we're getting somewhere, the number of segments will contribute to the
accuracy of the matching. The more segments the greater the matching
precision.

As a human I would _obviously_ calculate the length of the path, divide it by the desired number
of segments to get the segment length and then at each length interval add a
new coordinate... or... wait **there are more problems than answers in that
solution**:

- How to calculate the length?
- How to determine what the new coordinates should be?


### Route Length

I have been drawing curvy "analogue" routes, but in reality the route is a series of
straight lines:

{{< image "/images/2023-08-27/routes8.png" Resize "550x" "Routes are a series of straight lines" >}}

All we need to do is determine the length of each straight line. But how?
Pythagoras to the rescue!

The [Pythagorean Theorem](https://en.wikipedia.org/wiki/Pythagorean_theorem)
allows us to determine the length of hypotenuse in a right-angled triangle if
we have the lengths of the sides with the formular: `c = √(a² + b²)` (`c` is the
square root of the sum of `a` multiplied by `a` and `b` multiplied by `b`).

{{< image "/images/2023-08-27/routes9.png" Resize "300x" "Pythagorean Theorem" >}}

Our route can be represented as a bunch of triangles:

{{< image "/images/2023-08-27/routes10.png" Resize "300x" "Triangles" >}}

And for each triangle we can determine the length `c` of the sides `a` and `b`
and the sum of all those `c` values will be the length of our route.

Given the coordinates (a list of `x` and `y`):

```text
1.5, 2.5
2.5, 3.6
2.9, 2.9
...
```

We can then determine the distance between each coordinate by considering two
coordinates as a triangle and determining the length of the sides:

- side `a` has a length of `xᵢ - xᵢ₋₁` (e.g. `2.5 - 1.5 =
  0.75`)
- side `b` has a length of `yᵢ - yᵢ₋₁` (e.g. `3.6 - 2.5 =
  1.1`)
- the length of the line between the first and second coordinates is then
  `length = √(0.75² + 1.1²) = 1.3313`

So now we can calculate the length for each segment and sum them all to
provide a **length**.

## Plotting the New Coordinates

Now that we have the length of the original path and can determine the segment
length we can "walk" the path and record the coordinates where each segment
ends. Surely the only way to do this is with a **red carpet**:

As preparation:

- Ensure you have a very long (infinitely so if possible) **red carpet**.
- Cut the carpet to the length of the polyline
- Cut the carpet into 100 strips of equal length (increase number of strips
  for accuracy).
- Walk up to the first coordinate and roll out a strip of carpet
  matching the direction of the segment.

Then:

- Does the carpet extends further than the next coordinate?
- If so cut it at the intersection and place the remaining strip in the
  direction of the next coordinate.
- Otherwise _calculate`[*]`_ the **new co-ordinates** based on the direction of the old
  segment and the length of the carpet from the last intersection and **write
  them down**.
- Repeat.

{{< image "/images/2023-08-27/flow1.png" Resize "800x" "The only way to do this (a flowchart)">}}

Having finished you should have a list of the **normalized** co-ordinates, but
unfortunately there was a `[*]` as we didn't explain how to calculate the new
co-ordinates...

## [*] Calculating the New Co-ordinates

To determine the new co-ordinates we can determine the ratio (or percentage)
of the segment that is occupied by the slice of **red carpet**.

- Calculate the remaining length `c` from the last known carpet-coordinate to the end of the
  original segment using pythagoras as before:

{{< image "/images/2023-08-27/carpet1.png" Resize "800x" "Calculate the remaining distance">}}

- Divide the carpet slice length `sl` by the remaining length `c` to obtain the
  ratio or "percentage" (`ratio = sl / c`)
- Multiple the last known co-ordinates by the resulting ratio to obtain the
  new co-ordinates: `xᵢ₊₁ = xᵢ * ratio` and `yᵢ₊₁ = yᵢ * ratio`

## Scoring

Now we have two routes with the same number of co-ordinates and we
can directly compare them:

| Route A   | Route B   |
| ---       | ---       |
| 1.0, 0.5  | 1.1, 0.6  |
| 2.0, 1.3  | 1.9, 1.3  |
| 2.2, 0.3  | 2.1, 0.2  |


To do this we need to determine the length between the carpet points
and sum them up:

```text
0.38 = sum(
    √((1.0-1.1)² + (0.5-0.6)²) = 0.14
    √((2.0-1.9)² + (1.3-1.3)²) = 0.10
    √((2.2-2.1)² + (2.1-2.0)²) = 0.14
)
```

Given the three points above we get a similarity score of `0.38`!

## Summary

That's basically how the route matching works in Strava RS - for any given
route you can filter by "similarity". The route will currently be compared
against "all" routes which isn't very efficient, but this can be vastly
improved by pre-filtering by route length and perhaps then geographic boundaries.

{{< image "/images/2023-08-27/finish.png" Resize "600x" "Finish">}}
