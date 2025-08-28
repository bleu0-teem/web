
-- PlaySolo2006.lua
-- This script lets you play solo in classic Roblox Studio 2006.
-- It is a modified version of the original PlaySolo2006.lua script from:
--   https://archive.org/download/roblox-clients-2006-2021/
--
-- You are using this script for free and hosting on blue16.site.
-- Please do not remove these comments. Credit is appreciated! :3
-- Enjoy your blue16 server!

-- Services
local Visit = game:service("Visit")
local Players = game:service("Players")
local RunService = game:service("RunService")  
game.GuiRoot.MainMenu.Toolbox:remove()
game.GuiRoot.MainMenu["Edit Mode"]:remove() 

-- Create Player
local player = game.Players.LocalPlayer

if not player then
	player = game.Players:createLocalPlayer(0)
end

local function waitForChild(parent,childName)
	local child
	
	while true do
		child = parent:findFirstChild(childName)
		
		if child then
			break
		else
			parent.ChildAdded:wait()
		end
	end
	
	return child
end

local function onChanged(property)
	if property == "Character" then
		local humanoid = waitForChild(player.Character, "Humanoid")
		
		humanoid.Died:connect(function ()
			wait(5)
			player:LoadCharacter()
		end)
	end
end

player.Changed:connect(onChanged)
player:LoadCharacter()

-- Start the game.
RunService:run()