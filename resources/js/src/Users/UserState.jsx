import * as React from 'react';
import { Users, User, Shield, Clock } from 'lucide-react';

function UserStats({ stats }) {
  const formattedStats = {
    totalUsers: stats.totalUsers?.toLocaleString() || '0',
    activeUsers: stats.activeUsers?.toLocaleString() || '0',
    admins: stats.admins?.toLocaleString() || '0',
    newToday: stats.newToday?.toLocaleString() || '0',
  };

  return (
    <div className="grid grid-cols-4 gap-4">
      <div className="bg-black border border-blue-500/30 rounded-lg p-4 animate-fade-in-up delay-100">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Users className="h-5 w-5 text-blue-500" />
            <span className="text-sm text-blue-400 font-mono">TOTAL USERS</span>
          </div>
          <span className="text-xl font-mono text-blue-400">
            {formattedStats.totalUsers}
          </span>
        </div>
      </div>
      <div className="bg-black border border-blue-500/30 rounded-lg p-4 animate-fade-in-up delay-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <User className="h-5 w-5 text-green-500" />
            <span className="text-sm text-blue-400 font-mono">
              ACTIVE USERS
            </span>
          </div>
          <span className="text-xl font-mono text-green-400">
            {formattedStats.activeUsers}
          </span>
        </div>
      </div>
      <div className="bg-black border border-blue-500/30 rounded-lg p-4 animate-fade-in-up delay-300">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Shield className="h-5 w-5 text-amber-500" />
            <span className="text-sm text-blue-400 font-mono">ADMIN USERS</span>
          </div>
          <span className="text-xl font-mono text-amber-400">
            {formattedStats.admins}
          </span>
        </div>
      </div>
      <div className="bg-black border border-blue-500/30 rounded-lg p-4 animate-fade-in-up delay-400">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Clock className="h-5 w-5 text-purple-500" />
            <span className="text-sm text-blue-400 font-mono">NEW TODAY</span>
          </div>
          <span className="text-xl font-mono text-purple-400">
            {formattedStats.newToday}
          </span>
        </div>
      </div>
    </div>
  );
}

export default UserStats;
