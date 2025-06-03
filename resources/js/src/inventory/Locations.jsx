import * as React from "react";
import { Button } from "../ui/Button";

function LocationManagement({ className = "" }) {
  const [selectedLocation, setSelectedLocation] = React.useState(null);
  const [isManagingLocation, setIsManagingLocation] = React.useState(false);
  const [expandedLocations, setExpandedLocations] = React.useState(new Set());

  // Will be populated with data from API
  const locations = [];

  const toggleLocationExpand = (locationId) => {
    setExpandedLocations((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(locationId)) {
        newSet.delete(locationId);
      } else {
        newSet.add(locationId);
      }
      return newSet;
    });
  };

  // Helper function to format timestamp
  const formatDate = (timestamp) => {
    if (!timestamp) return "N/A";
    return new Date(timestamp).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };

  const renderLocationTree = (locations, level = 0) => {
    return locations.map((location) => {
      const hasChildren = location.children && location.children.length > 0;
      const isExpanded = expandedLocations.has(location.id);

      return (
        <div key={location.id} className="mb-1">
          <div
            className={`flex items-center pl-${level * 4} py-1 rounded hover:bg-neutral-800 cursor-pointer group ${
              selectedLocation?.id === location.id ? "bg-neutral-800" : ""
            }`}
            onClick={() => setSelectedLocation(location)}
          >
            <div
              className="w-5 h-5 flex items-center justify-center mr-1"
              onClick={(e) => {
                e.stopPropagation();
                if (hasChildren) toggleLocationExpand(location.id);
              }}
            >
              {hasChildren ? (
                isExpanded ? (
                  <span className="text-sm">▼</span>
                ) : (
                  <span className="text-sm">▶</span>
                )
              ) : (
                <div className="w-3.5"></div>
              )}
            </div>
            <div
              className={`flex-1 text-sm truncate ${
                !location.is_active ? "text-neutral-500" : ""
              }`}
            >
              {location.name}
              <span className="ml-2 text-xs text-neutral-500">
                {location.code}
              </span>
            </div>
            <div className="flex space-x-1">
              <Button
                variant="ghost"
                size="icon"
                className="h-6 w-6 opacity-0 group-hover:opacity-100 hover:bg-neutral-700 dark:hover:bg-neutral-600"
                aria-label={`Edit ${location.name}`}
              >
                <span className="text-sm">✏️</span>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-6 w-6 opacity-0 group-hover:opacity-100 hover:bg-neutral-700 dark:hover:bg-neutral-600"
                aria-label={`Delete ${location.name}`}
              >
                <span className="text-sm">🗑️</span>
              </Button>
            </div>
          </div>

          {hasChildren && isExpanded && (
            <div className="pl-5">{renderLocationTree(location.children, level + 1)}</div>
          )}
        </div>
      );
    });
  };

  const renderLocationDetails = () => {
    if (!selectedLocation) return null;

    return (
      <div className="h-full flex flex-col">
        <div className="bg-neutral-800 px-3 py-2 flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7"
              onClick={() => setIsManagingLocation(false)}
              aria-label="Back to location list"
            >
              <span className="text-sm">←</span>
            </Button>
            <h3 className="text-sm font-mono">Location Details</h3>
          </div>
          <div
            className={`px-2 py-0.5 rounded-sm text-xs ${
              selectedLocation.is_active
                ? "bg-green-500/20 text-green-400"
                : "bg-neutral-700 text-neutral-400"
            }`}
          >
            {selectedLocation.is_active ? "ACTIVE" : "INACTIVE"}
          </div>
        </div>

        <div className="p-4 flex-1 overflow-auto">
          <div className="flex items-start space-x-3 mb-6">
            <div className="h-16 w-16 rounded bg-indigo-900/30 text-indigo-500 flex items-center justify-center">
              <span className="text-2xl">🗺️</span>
            </div>
            <div>
              <h3 className="text-lg font-medium">{selectedLocation.name}</h3>
              <div className="text-sm text-neutral-400">
                Code: {selectedLocation.code}
              </div>
              <div className="flex items-center mt-1 space-x-2">
                <span className="text-xs text-neutral-500">
                  ID: {selectedLocation.id}
                </span>
                <span className="text-xs text-neutral-500">
                  Path: {selectedLocation.path || "None"}
                </span>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3 mb-6">
            <Button
              variant="outline"
              className={`flex items-center space-x-2 ${
                selectedLocation.is_active
                  ? "border-red-800 hover:border-red-700 text-red-500"
                  : "border-green-800 hover:border-green-700 text-green-500"
              }`}
              aria-label={
                selectedLocation.is_active
                  ? "Deactivate location"
                  : "Activate location"
              }
            >
              {selectedLocation.is_active ? (
                <span className="text-sm">✖</span>
              ) : (
                <span className="text-sm">✔</span>
              )}
              <span>{selectedLocation.is_active ? "Deactivate" : "Activate"}</span>
            </Button>
            <Button
              variant="outline"
              className="border-indigo-800 hover:border-indigo-700 text-indigo-500 flex items-center space-x-2"
              aria-label="Edit location"
            >
              <span className="text-sm">✏️</span>
              <span>Edit Details</span>
            </Button>
          </div>

          <div className="space-y-4 mb-6">
            <div className="bg-neutral-800 rounded-md p-3">
              <h4 className="text-sm font-mono mb-3">LOCATION DETAILS</h4>
              <div className="space-y-2 text-xs text-neutral-400">
                <div className="flex justify-between">
                  <span>Parent Location</span>
                  <span className="text-neutral-200">
                    {selectedLocation.parent_id
                      ? locations.find((l) => l.id === selectedLocation.parent_id)
                          ?.name || `ID: ${selectedLocation.parent_id}`
                      : "None (Root Location)"}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Created</span>
                  <span className="text-neutral-200">
                    {formatDate(selectedLocation.created_at)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Last Updated</span>
                  <span className="text-neutral-200">
                    {formatDate(selectedLocation.updated_at)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Status</span>
                  <span
                    className={
                      selectedLocation.is_active ? "text-green-400" : "text-red-400"
                    }
                  >
                    {selectedLocation.is_active ? "Active" : "Inactive"}
                  </span>
                </div>
              </div>
            </div>

            {selectedLocation.children && selectedLocation.children.length > 0 && (
              <div className="bg-neutral-800 rounded-md p-3">
                <h4 className="text-sm font-mono mb-3">
                  SUB-LOCATIONS ({selectedLocation.children.length})
                </h4>
                <div className="space-y-1">
                  {selectedLocation.children.map((child) => (
                    <div
                      key={child.id}
                      className="flex items-center justify-between py-1 hover:bg-neutral-750"
                    >
                      <span className="text-sm">{child.name}</span>
                      <span
                        className={`text-xs ${
                          child.is_active ? "text-green-400" : "text-neutral-500"
                        }`}
                      >
                        {child.code}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="bg-red-950/20 border border-red-900/50 rounded-md p-3">
            <h4 className="text-sm font-mono text-red-500 mb-2">DANGER ZONE</h4>
            <Button
              variant="outline"
              className="w-full border-red-800 hover:border-red-700 hover:bg-red-950/30 text-red-500 flex items-center justify-center space-x-2"
              aria-label="Delete location"
            >
              <span className="text-sm">🗑️</span>
              <span>Delete Location</span>
            </Button>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div
      className={`bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden ${className} relative dark:bg-neutral-900 dark:border-neutral-800`}
    >
      <div
        className={`w-full transition-transform duration-300 ${
          isManagingLocation ? "transform -translate-x-full" : ""
        }`}
      >
        <div className="flex items-center justify-between bg-neutral-800 px-3 py-2">
          <div className="flex items-center space-x-2">
            <span className="text-indigo-500 text-base">📁</span>
            <h2 className="text-sm font-mono">Location Management</h2>
          </div>
          <div className="flex items-center space-x-1.5">
            <Button
              variant="ghost"
              size="sm"
              className="h-6 text-xs"
              aria-label="Add new location"
            >
              <span className="text-sm mr-1.5">➕</span>
              Add Location
            </Button>
          </div>
        </div>

        <div className="p-3">
          <div className="relative mb-3">
            <span className="absolute left-2 top-1/2 -translate-y-1/2 text-neutral-600 text-sm">🔍</span>
            <input
              type="search"
              placeholder="Search locations..."
              className="w-full bg-neutral-800 text-sm rounded-md py-1.5 pl-8 pr-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:bg-neutral-800 dark:focus:ring-indigo-400"
              aria-label="Search locations"
            />
          </div>

          <div className="bg-neutral-800 rounded-md p-2">
            <div className="mb-2 font-medium text-xs text-neutral-400 uppercase">
              Location Hierarchy
            </div>
            {renderLocationTree(locations)}
          </div>

          {selectedLocation && (
            <div className="mt-3">
              <Button
                variant="outline"
                className="w-full border-indigo-800 hover:border-indigo-700 text-indigo-500 flex items-center justify-center space-x-2 dark:border-indigo-700 dark:hover:border-indigo-600 dark:text-indigo-400"
                onClick={() => setIsManagingLocation(true)}
              >
                <span>View Details</span>
                <span className="text-sm">▶</span>
              </Button>
            </div>
          )}
        </div>
      </div>

      <div
        className={`absolute inset-0 w-full h-full bg-neutral-900 transition-transform duration-300 ${
          isManagingLocation ? "" : "transform translate-x-full"
        } dark:bg-neutral-900`}
      >
        {selectedLocation && renderLocationDetails()}
      </div>
    </div>
  );
}

export default LocationManagement;