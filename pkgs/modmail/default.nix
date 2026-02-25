{
  lib,
  buildPythonApplication,
  poetry-core,
  pythonRelaxDepsHook,
  aiodns,
  aiohttp,
  aiosignal,
  async-timeout,
  attrs,
  brotli,
  cairocffi,
  cairosvg,
  certifi,
  cffi,
  charset-normalizer,
  colorama,
  cssselect2,
  defusedxml,
  discordpy,
  dnspython,
  emoji,
  frozenlist,
  idna,
  isodate,
  lottie,
  motor,
  multidict,
  natural,
  orjson,
  packaging,
  parsedatetime,
  pillow,
  pycares,
  pycparser,
  pymongo,
  python-dateutil,
  python-dotenv,
  requests,
  six,
  tinycss2,
  urllib3,
  uvloop,
  webencodings,
  yarl,

  self,
}:
buildPythonApplication {
  pname = "modmail";
  version = "${self.lastModifiedDate or "unknown"}-${self.shortRev or self.dirtyShortRev or "dirty"}";
  pyproject = true;

  src = lib.fileset.toSource {
    root = ../../.;
    fileset = lib.fileset.unions [
      ../../cogs
      ../../modmail
      ../../plugins
      ../../README.md
      ../../pyproject.toml
      ../../poetry.lock
    ];
  };

  nativeBuildInputs = [
    poetry-core
    pythonRelaxDepsHook
  ];

  propagatedBuildInputs = [
    aiodns
    aiohttp
    aiosignal
    async-timeout
    attrs
    brotli
    cairocffi
    cairosvg
    certifi
    cffi
    charset-normalizer
    colorama
    cssselect2
    defusedxml
    discordpy
    dnspython
    emoji
    frozenlist
    idna
    isodate
    lottie
    motor
    multidict
    natural
    orjson
    packaging
    parsedatetime
    pillow
    pycares
    pycparser
    pymongo
    python-dateutil
    python-dotenv
    requests
    six
    tinycss2
    urllib3
    uvloop
    webencodings
    yarl
  ];

  pythonRelaxDeps = [
    "attrs"
    "defusedxml"
    "packaging"
    "pillow"
    "uvloop"
  ];

  pythonImportsCheck = [ "modmail" ];

  meta = {
    description = "A Discord bot that functions as a shared inbox between staff and members, similar to Reddit's Modmail";
    homepage = "https://github.com/modmail-dev/Modmail";
    license = lib.licenses.agpl3Only;
    maintainers = with lib.maintainers; [ Scrumplex ];
    mainProgram = "modmail";
  };
}
