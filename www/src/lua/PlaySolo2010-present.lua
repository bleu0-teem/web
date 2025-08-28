
-- PlaySolo2010-present.lua
-- This script lets you play solo in old Roblox Studio versions.
-- It is a modified version of the original PlaySolo2010-present.lua scripts from:
--   https://archive.org/download/roblox-clients-2006-2021/
--
-- You are using this script for free and hosting on blue16.site.
-- Please do not remove these comments. Credit is appreciated! :3
-- Enjoy your blue16 server!

local plr = game.Players:CreateLocalPlayer(0)
game:GetService("Visit")
game:GetService("RunService"):run()
plr:LoadCharacter()
print ("Play in the old studio with this.")
while true do wait(0.001)
if plr.Character.Humanoid.Health == 0
then wait(5) plr:LoadCharacter()
print ("LocalPlayer was killed.")
end
end