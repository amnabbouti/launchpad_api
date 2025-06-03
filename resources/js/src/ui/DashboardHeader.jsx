import * as React from "react";
import { usePage, router } from "@inertiajs/react";
import { Button } from "./Button";

function DashboardHeader() {
  const [currentTime, setCurrentTime] = React.useState(null); // Initialize as null
  const { url } = usePage();

  // Update time every second
  React.useEffect(() => {
    // Set initial time on client side
    setCurrentTime(getFormattedTime());

    const interval = setInterval(() => {
      setCurrentTime(getFormattedTime());
    }, 1000);

    return () => clearInterval(interval);
  }, []);

  // Format time as HH:MM:SS
  const getFormattedTime = () => {
    return new Date().toLocaleTimeString("en-US", {
      hour12: false,
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
  };

  // Get page title from URL
  const getPageTitle = () => {
    const path = url.split("/").filter(Boolean);

    if (path.length === 1 && path[0] === "dashboard") return "Dashboard";

    if (path.length >= 2) {
      const lastSegment = path[path.length - 1];
      return lastSegment
        .split("-")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
    }

    return "Dashboard";
  };

  // Show back button if not on main dashboard
  const showBackButton = url !== "/dashboard";

  // Navigate back
  const handleGoBack = () => {
    window.history.back();
  };

  return (
    <header className="sticky top-0 z-10 bg-neutral-900 dark:bg-neutral-900 border-b border-neutral-800 dark:border-neutral-800 px-4 py-2">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2">
          {showBackButton && (
            <Button
              size="sm"
              variant="outline"
              className="h-7 mr-2 border-neutral-700 bg-neutral-800 hover:bg-neutral-700 dark:border-neutral-600 dark:bg-neutral-800 dark:hover:bg-neutral-700"
              onClick={handleGoBack}
            >
              <svg
                className="h-3.5 w-3.5 mr-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                strokeWidth="2"
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Back
            </Button>
          )}
          <h1 className="font-mono text-lg text-blue-500 dark:text-blue-400">{getPageTitle()}</h1>
        </div>

        <div className="flex items-center space-x-2">
          <span className="text-xs font-mono text-neutral-500 dark:text-neutral-400">
            {currentTime || ""}
          </span>
          <Button
            size="sm"
            variant="outline"
            className="h-7 border-neutral-700 bg-neutral-800 hover:bg-neutral-700 dark:border-neutral-600 dark:bg-neutral-800 dark:hover:bg-neutral-700"
            onClick={() => window.location.reload()}
          >
            <svg
              className="h-3.5 w-3.5 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              strokeWidth="2"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            Refresh
          </Button>
        </div>
      </div>
    </header>
  );
}

export default DashboardHeader;