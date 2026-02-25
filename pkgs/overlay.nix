{ self, ... }:
{
  overlays.default = final: prev: {
    modmail = final.python311Packages.callPackage ./modmail { inherit self; };
  };
}
