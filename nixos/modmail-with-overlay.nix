self:
{ ... }:
{
  imports = [ ./modmail.nix ];

  nixpkgs.overlays = [ self.overlays.default ];
}
