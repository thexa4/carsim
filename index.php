<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Ethics</title>
<!--  This code is in public domain  -->
<script src="hexi.min.js"></script>
  </head>
  <body>
<a href="https://github.com/thexa4/carsim">source code</a><br>
<script>
var files = ["road.png",
    "plus.png",
    "min.png"];
var g = hexi(500, 500, setup, files);
var gu = new GameUtilities();
//var b = new Bump(g);
var u = new SpriteUtilities(PIXI);

var w_laneholding = 0.5;
var w_speed = 0.5;
var w_repulsion = 0.8;
var w_repulse_offset = 90;


var scene, cargroup, gravity, camera, leftlane, rightlane, lanegraphic;

var laneplus, lanemin, speedplus, speedmin, repulseplus, repulsemin, offsetplus, offsetmin;
var laneweight, speedweight, repulseweight, offsetweight;
var comfortlabel ;
var  car;
var cars = [];

g.start();

function Lane(fromx, tox, leftstr, rightstr) {
    this.repulse = function(car, x, y) {
        var dx = 0;
        if (x < fromx)
            dx = Math.min(1, (fromx - x) / 100) * leftstr 
        if (x > tox)
            dx = Math.max(-1, -(x - tox) / 100) * rightstr 
        if(dx == 0)
            dx = -car.vx * w_laneholding
        return [dx,0];
    }
}

function Vehicle(x, y, dv) {
    var result = g.rectangle(32, 64, "red", "black", 1, x, y);
    result.setPivot(0.5, 0.5);
    if(dv < 0) 
        result.rotation = Math.PI;
    else
        result.rotation = 0;
    result.speed = 0;

    result.modifiers = [];
    result.addMod = function(f) { result.modifiers.push(f); }
    result.calcModifiers = function() {
        var resx = 0.0;
        var resy = 0.0;

        result.modifiers.forEach(function(repulsor, i, a) { 
            var arr = repulsor.repulse(result, result.x, result.y);
            resx += Math.min(Math.max(-5, arr[0]), 5);
            resy += Math.min(Math.max(-5, arr[1]), 5);
        });

        return [resx, resy];
    }
    result.comfort = 0;
    result.type = "default";
    result.weightfunc = function(other) { return w_repulse_offset; }
    result.desiredSpeed = dv;
    result.maxAccel = 0.1;

    result.crashed = false;

    result.addMod(gravity);

    result.update = function() {
        if(result.crashed)
        {
            result.vx = 0;
            result.vy = 0;
            result.fillStyle = "gray";
            return;
        }

        var arr = result.calcModifiers();

        var fwx = Math.sin(-result.rotation);
        var fwy = Math.cos(-result.rotation);

        var rightx = Math.sin(-result.rotation - Math.PI / 2);
        var righty = Math.cos(-result.rotation - Math.PI / 2);

        var gas = fwx * arr[0] + fwy * arr[1];
        var steer = rightx * arr[0] + righty * arr[1];

        result.comfort += w_speed - Math.abs(steer);
        result.speed += Math.max(Math.min(0.2, gas), -Math.max(0.1, result.speed / 2));
        result.rotation += Math.max(Math.min(0.6, steer / 300), -0.6);

        if(result.speed < 0)
            result.speed = 0;

        result.vx = Math.sin(-result.rotation) * result.speed;
        result.vy = Math.cos(-result.rotation) * result.speed;
    }

    result.repulse = function(other) {
        if(g.hit(other, result))
            other.crashed = true;

        var mod = result.weightfunc(other);
        var dx = other.x - result.x;
        var dy = other.y - result.y;

        var dist = Math.sqrt(dx * dx + dy * dy);
        dx = dx / dist;
        dy = dy / dist;

        var force = mod - dist;
        var alignment = Math.sin(-other.rotation) * -dx + Math.cos(-other.rotation) * -dy
        if (force < 0 || alignment < 0)
            return [0, 0];

        return [dx * force * alignment * w_repulsion / 1000 * (result.width + other.width), dy * force * alignment * w_repulsion / 1000 * (result.height + other.height)];
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
        var diff = car.vy - car.desiredSpeed;
        if(diff > car.maxaccel)
            diff = car.maxaccel;
        if(diff < -car.maxaccel)
            dif = -car.maxaccel;
        return [0, -diff * w_speed];
    }}
    scene = g.group();

    leftlane = new Lane(28, 60, 3, 1);
    rightlane = new Lane(78, 100, 1, 3);
    lanegraphic = g.tilingSprite("road.png", 127, 10240, 0, -9400);
    scene.addChild(lanegraphic);

    car = Vehicle(90, 400, -3);
    scene.addChild(car);
    for(var i = 0; i < 30; i++)
        if(g.randomInt(0, 1) == 0)
            scene.addChild(Vehicle(g.randomInt(0, 30), g.randomInt(-6800, 000), g.randomFloat(1, 2)));
        else
            scene.addChild(Vehicle(g.randomInt(100, 144), g.randomInt(-6800, 000), g.randomFloat(-2, 1)));
    camera = g.worldCamera(scene, 300, 300);
    scene.visible = true;
    g.state = play;
    
    
    //var laneplus, lanemin, speedplus, speedmin, repulseplus, repulsemin, offsetplus, offsetmin;
    //var laneweight, speedweight, repulseweight, offsetweight;

    laneweight = g.text("Laneholding: " + w_laneholding, "16px sans", "black", 270, 100);
    speedweight = g.text("Speed: " + w_speed, "16px sans", "black", 270, 130);
    repulseweight = g.text("Repulsion: " + w_repulsion, "16px sans", "black", 270, 160);
    offsetweight = g.text("Distance: " + w_repulse_offset, "16px sans", "black", 270, 190);

    laneplus = g.sprite("plus.png", 420, 102);
    laneplus.width = 16;
    laneplus.height = 16;
    speedplus = g.sprite("plus.png", 420, 132);
    speedplus.width = 16;
    speedplus.height = 16;
    repulseplus = g.sprite("plus.png", 420, 162);
    repulseplus.width = 16;
    repulseplus.height = 16;
    offsetplus = g.sprite("plus.png", 420, 192);
    offsetplus.width = 16;
    offsetplus.height = 16;
    
    lanemin = g.sprite("min.png", 460, 102);
    lanemin.width = 16;
    lanemin.height = 16;
    speedmin = g.sprite("min.png", 460, 132);
    speedmin.width = 16;
    speedmin.height = 16;
    repulsemin = g.sprite("min.png", 460, 162);
    repulsemin.width = 16;
    repulsemin.height = 16;
    offsetmin = g.sprite("min.png", 460, 192);
    offsetmin.width = 16;
    offsetmin.height = 16;

    g.makeInteractive(laneplus);
    g.makeInteractive(speedplus);
    g.makeInteractive(repulseplus);
    g.makeInteractive(offsetplus);
    g.makeInteractive(lanemin);
    g.makeInteractive(speedmin);
    g.makeInteractive(repulsemin);
    g.makeInteractive(offsetmin);

    laneplus.tap = function() {
        w_laneholding += 0.2;
    };
    lanemin.tap = function() {
        w_laneholding -= 0.2;
    }

    speedplus.tap = function() {
        w_speed += 0.1;
    }
    speedmin.tap = function() {
        w_speed += -0.1;
    }
    
    repulseplus.tap = function() {
        w_repulsion += 0.2;
    }
    repulsemin.tap = function() {
        w_repulsion -= 0.2;
    }

    offsetplus.tap = function() {
        w_repulse_offset += 5;
    }
    offsetmin.tap = function() {
        w_repulse_offset -= 5;
    }

    comfortlabel = g.text("Comfort: 0", "24px sans", 270, 300);
}

function play() {
    cars.forEach(function(car, i, a) { 
        car.update();
        g.move(car);
    });

    laneweight.text = "Laneholding: " + w_laneholding; 
    speedweight.text = "Speed: " + w_speed;
    repulseweight.text ="Repulsion: " + w_repulsion;
    offsetweight.text="Distance: " + w_repulse_offset;

    comfortlabel.text = "Comfort: " + car.comfort;

    camera.centerOver(cars[0]);
}
</script>  
    <!-- page content -->
  </body>
</html>
