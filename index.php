<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Ethics</title>
    <!--<link rel="stylesheet" href="style.css">-->
<script src="hexi.min.js"></script>
  </head>
  <body>
<script>
let files = [];
let g = hexi(300, 500, setup);
let gu = new GameUtilities();
//let b = new Bump(g);


let scene, gravity, camera, leftlane, rightlane;


let cars = [];

g.start();

function Lane(fromx, tox) {
    this.repulse = function(car, x, y) {
        let dx = 0;
        if (x < fromx)
            dx = Math.min(1, (fromx - x) / 100)
        if (x > tox)
            dx = Math.max(-1, -(x - tox) / 100)
        if(dx == 0)
            dx = -car.vx / 10
        return [dx,0];
    }
}

function Vehicle(x, y, dv) {
    let result = g.rectangle(32, 64, "red", "black", 1, x, y);
    result.modifiers = [];
    result.addMod = function(f) { result.modifiers.push(f); }
    result.calcModifiers = function() {
        let resx = 0.0;
        let resy = 0.0;

        result.modifiers.forEach(function(repulsor, i, a) { 
            let arr = repulsor.repulse(result, result.x, result.y);
            resx += arr[0];
            resy += arr[1];
        });

        return [resx, resy];
    }

    result.type = "default";
    result.weightfunc = function(other) { return 80; }
    result.desiredSpeed = dv;
    result.maxAccel = 0.1;

    result.addMod(gravity);

    result.update = function() {
        let arr = result.calcModifiers();
        result.vx += arr[0];
        result.vy += arr[1];
    }

    result.repulse = function(other) {
        let mod = result.weightfunc(other);
        let dx = result.x - other.x;
        let dy = result.y - other.y;

        let dist = Math.sqrt(dx * dx + dy * dy);
        dx = dx / dist;
        dy = dy / dist;

        let force = mod - dist;
        if (force < 0)
            return [0, 0];

        return [-dx * force / 1000 * (result.width + other.width), -dy * force / 1000 * (result.height + other.height)];
    }

    if(dv > 0)
        result.addMod(leftlane);
    else
        result.addMod(rightlane);

    cars.forEach(function(car, i, a) { car.addMod(result); result.addMod(car); });
    cars.push(result);
    return result;
}

function load() {
    g.loadingBar();
}

function setup() {
    console.log("setup");

    gravity = { repulse: function(car, x, y) { 
        let diff = car.vy - car.desiredSpeed;
        if(diff > car.maxaccel)
            diff = car.maxaccel;
        if(diff < -car.maxaccel)
            dif = -car.maxaccel;
        return [0, -diff];
    }}
    leftlane = new Lane(0, 64);
    rightlane = new Lane(80, 144);

    scene = g.group();
    let car = Vehicle(16, 400, -3);
    scene.addChild(car);
    for(var i = 0; i < 30; i++)
        scene.addChild(Vehicle(g.randomInt(0, 144), g.randomInt(-800, 800), g.randomFloat(-4, 4)));
    camera = g.worldCamera(scene, 300, 300);
    scene.visible = true;
    g.state = play;
}

function play() {
    cars.forEach(function(car, i, a) { 
        car.update();
        g.move(car);
    });

    camera.centerOver(cars[0]);
}
</script>  
    <!-- page content -->
  </body>
</html>
