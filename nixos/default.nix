{ self, ... }:
{
  nixosModules = {
    modmail = ./modmail.nix;
    modmail-with-overlay = import ./modmail-with-overlay.nix self;
  };
}
