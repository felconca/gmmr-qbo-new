(function () {
  // helpers
  function loadScript(url) {
    return new Promise(function (resolve, reject) {
      const s = document.createElement("script");
      s.src = url;
      s.async = false;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function loadCSS(url) {
    return new Promise(function (resolve, reject) {
      const link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = url;
      link.onload = resolve;
      link.onerror = reject;
      document.head.appendChild(link);
    });
  }

  // find the script tag that loaded this file
  function getAppName() {
    const scripts = document.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      const script = scripts[i];
      if (script.src.includes("bootstrapper.js")) {
        // check for "app" attribute in script tag
        return script.getAttribute("app") || "mainApp";
      }
    }
    // fallback
    return "mainApp";
  }

  async function load() {
    try {
      const appName = getAppName();
      console.log(`🚀 Bootstrapping Angular app: ${appName}`);

      for (const entry of window.APP_MODULES) {
        const files = typeof entry === "string" ? [entry] : entry.files;

        for (const file of files) {
          if (file.endsWith(".js")) await loadScript(file);
          else if (file.endsWith(".css")) await loadCSS(file);
        }
      }

      console.log("✅ Dependencies loaded. Bootstrapping Angular...");
      angular.element(document).ready(function () {
        angular.bootstrap(document, [appName]);
      });
    } catch (err) {
      console.error("❌ Failed to load dependencies:", err);
    }
  }

  load();
})();
