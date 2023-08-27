--- 
title: "Comparing Paths"
categories: [strava]
date: 2023-08-27
draft: true
---

[Strava](https://www.strava.com) is a service that allows you to record,
analyze and share your sporting activities.

[Strava RS](https://github.com/dantleech/strava-rs) is a TUI (terminal user
interface) client for Strava that I'm working on. It provides an offline,
keyboard driven, interface to Strava using data collected fro the [Strava
API](https://developers.strava.com/docs/reference/).

One of the feature the Strava web interface provides is "Matched Routes": if
the activity you performed closely matches the route of any previous
activities, you can list and compare your previous performances.

In Strava this is a premium feature. I wanted to implement this feature in
Strava RS, which essentially boils down to: **How similar is route A to route
B**?

I had no idea how to answer this question or even what to Google for, so I
figured it out for myself which is always fun!

In this post I want to explain how I answered this question.

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

![routes](/images/2023-08-27/routes1.png)

Above we can clearly see that routes 1, 2 and 4 are the same shapes, and that
3 is clearly different. In this case 3 is actually the
[Dorchester](https://www.parkrun.org.uk/thegreatfield/) ParkRun:

![routes](/images/2023-08-27/route3.png)

Route 3 doesn't intersect with our run in Weymouth. Let's overlay the 3
Weymouth Parkruns:

![routes](/images/2023-08-27/routes2.png)

As a human we can clearly see they are the same:

- They share the same geographical area.
- The paths are very close together.

But how to get a computer to recognise this? We can calculate the distance
between the points.

## Similarity Score

![routes](/images/2023-08-27/routes4.png)

Adding all the distances gives a score, in this case `0.76` and we can say that
the black route has a **similarity score** of `0.76` against the green route.

![routes](/images/2023-08-27/routes5.png)

Dorchester and Weymouth are clearly not the same route giving a similarity
score of `481`, so we could say that if the similarity score is less than
`1.0` then we have a match! This seems like a good approach

However, as a **human** I was able to draw some arbitrary lines to geographically
similar points, but unfortunately making the computer do this would be
a whole different problem to solve. How do we determine which points to
compare?

## Normalisation

![routes](/images/2023-08-27/routes6.png)

Route A has 20 coordinates and route B has 15 coordinates (vastly simplified).

We can _normalize_ the routes to have the same number of coordinates, we can
then score each coordinate against it's counterpart:

![routes](/images/2023-08-27/routes7.png)

So we can now summarise the proposal

- **Normalize** the two routes so that they have the same number of coordinates.
- Sum the difference between the opposing coordinates to produce a **similarity score**.

Now we're getting somewhere, the number of segments will contribute to the
accuracy of the matching. The more segments the greater the matching
precision.

As a human I would obviously calculate the length of the path, divide it by the desired number
of segments to get the segment length and then at each length interval add a
new coordinate... or... wait there are more problems than answers in that
solution:

- How to calculate the length?
- How to determine what the new coordinates should be?


### Route Length

I have been drawing "analogue" routes, but in reality the route is a series of
straight lines:

![routes](/images/2023-08-27/routes8.png)

All we need to do is determine the length of each straight line. But how?
Pythagoras to the rescue!

The [Pythagorean Theorum](https://en.wikipedia.org/wiki/Pythagorean_theorem)
allows us to determine the length of hypotenuse in a right-angled triangle if
we have the lengths of the sides with the formular: `c = √(a² + b²)` (`c` is the
square root of the sum of `a` multiplied by `a` and `b` multiplied by `b`).

![routes](/images/2023-08-27/routes9.png)

Our route can be represented as a bunch of triangles:

![routes](/images/2023-08-27/routes10.png)

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

- side `a` has a length of `x² - x¹` (e.g. `2.5 - 1.5 =
  0.75`)
- side `b` has a length of `y² - y¹` (e.g. `3.6 - 2.5 =
  1.1`)
- the length of the line between the first and second coordinates is then
  `length = √(0.75² + 1.1²) = 1.3313`

So now we can just calculate the length for each segment and sum them all to
provide a length.

## Plotting the new coordinates

This is the tricky part. Now that we have the length of the original path we can divide it into a
specified number of segments of a specific length `sl`.

"We need now to trace the original path and record a coordinate each time we
get to the end of a segment.

// TODO




