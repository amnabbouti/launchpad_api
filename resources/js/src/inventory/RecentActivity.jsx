import * as React from 'react';
import { Clock, Plus, Minus, Edit } from 'lucide-react';

function RecentActivity({ activities = [] }) {
  if (!activities || activities.length === 0) {
    return (
      <div className="text-center text-neutral-400 py-4">
        No recent activity found.
      </div>
    );
  }

  const getActivityIcon = (type) => {
    switch (type) {
      case 'added':
        return <Plus className="h-4 w-4 text-green-400" />;
      case 'removed':
        return <Minus className="h-4 w-4 text-red-400" />;
      case 'updated':
        return <Edit className="h-4 w-4 text-blue-400" />;
      default:
        return <Clock className="h-4 w-4 text-neutral-400" />;
    }
  };

  return (
    <div className="space-y-3">
      <h4 className="text-sm font-medium text-neutral-300 mb-3">
        Recent Activity
      </h4>
      <div className="space-y-2">
        {activities.map((activity, index) => (
          <div
            key={index}
            className="flex items-start space-x-3 p-2 rounded-md bg-neutral-800/30"
          >
            <div className="flex-shrink-0 mt-0.5">
              {getActivityIcon(activity.type)}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm text-neutral-300">{activity.description}</p>
              <p className="text-xs text-neutral-500 mt-1">
                {activity.timestamp || 'Just now'}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default RecentActivity;
