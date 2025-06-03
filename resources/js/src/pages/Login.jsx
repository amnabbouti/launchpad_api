import * as React from "react";
import { Rocket, Moon, Earth, Star, Lock, User, Eye, EyeOff } from "lucide-react";
import { Button } from "../ui/Button";
import { Input } from "../ui/Input";
import { Label } from "../ui/Label";
import { useForm } from "@inertiajs/react";

function Login() {
  const [showPassword, setShowPassword] = React.useState(false);
  const { data, setData, post, processing, errors } = useForm({
    email: "",
    password: "",
  });

  const handleLogin = (e) => {
    e.preventDefault();
    post('/admin/login');
  };

  return (
    <div className="min-h-screen bg-black text-white overflow-hidden relative">
      <style>
        {`
          @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.5); }
            50% { opacity: 1; transform: scale(1); }
          }
          @keyframes rotateEarth {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
          @keyframes bounceMoon {
            0%, 100% { transform: translateY(-10px); }
            50% { transform: translateY(10px); }
          }
          @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
          }
          @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
          }
          @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
          }
          @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
          }
          @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
          .animate-twinkle {
            animation: twinkle 5s infinite;
            animation-delay: calc(var(--delay) * 1s);
          }
          .animate-rotate-earth {
            animation: rotateEarth 120s linear infinite;
          }
          .animate-bounce-moon {
            animation: bounceMoon 8s ease-in-out infinite;
          }
          .animate-fade-in {
            animation: fadeIn 1s ease-out;
          }
          .animate-slide-in-left {
            animation: slideInLeft 1s ease-out;
          }
          .animate-slide-in-right {
            animation: slideInRight 1s ease-out;
          }
          .animate-slide-in-up {
            animation: slideInUp 1s ease-out;
          }
          .animate-pulse {
            animation: pulse 2s infinite;
          }
          .animate-spin {
            animation: spin 1s linear infinite;
          }
          .delay-200 { animation-delay: 0.2s; }
          .delay-400 { animation-delay: 0.4s; }
          .delay-600 { animation-delay: 0.6s; }
          .delay-800 { animation-delay: 0.8s; }
          .delay-1000 { animation-delay: 1s; }
          .delay-1200 { animation-delay: 1.2s; }
          .delay-1500 { animation-delay: 1.5s; }
        `}
      </style>

      {/* Background Animation - Stars */}
      <div className="absolute inset-0">
        {[...Array(100)].map((_, i) => (
          <div
            key={i}
            className="absolute w-1 h-1 bg-white rounded-full opacity-60 animate-twinkle"
            style={{
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              "--delay": Math.random() * 2,
            }}
          />
        ))}
      </div>

      {/* Earth in background */}
      <div
        className="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-gradient-to-br from-blue-500 via-green-400 to-blue-600 opacity-20 animate-rotate-earth"
      />

      {/* Moon */}
      <div
        className="absolute top-20 right-20 w-24 h-24 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 opacity-40 animate-bounce-moon"
      />

      <div className="relative z-10 min-h-screen flex">
        {/* Left Side - Mission Control Aesthetic */}
        <div className="flex-1 flex flex-col justify-center items-center p-8">
          <div className="max-w-md w-full animate-slide-in-left">
            {/* Mission Header */}
            <div className="text-center mb-8">
              <div className="flex justify-center mb-4">
                <div className="relative">
                  <Rocket className="h-16 w-16 text-blue-400 animate-fade-in delay-200" />
                  <div
                    className="absolute -bottom-2 -right-2 w-6 h-6 bg-red-500 rounded-full animate-pulse"
                  />
                </div>
              </div>

              <h1
                className="text-3xl font-mono font-bold mb-2 tracking-wider animate-fade-in delay-400"
              >
                MISSION CONTROL
              </h1>

              <div
                className="text-sm text-blue-400 font-mono tracking-widest animate-fade-in delay-600"
              >
                APOLLO AUTHENTICATION SYSTEM
              </div>

              <div
                className="text-xs text-gray-400 mt-2 font-mono animate-fade-in delay-800"
              >
                [ SECURE HOUSTON ACCESS TERMINAL ]
              </div>
            </div>

            {/* Login Form */}
            <div className="space-y-6 animate-slide-in-up delay-1000">
              <div className="bg-neutral-900/50 backdrop-blur-sm border border-blue-500/30 rounded-lg p-6 shadow-2xl">
                <form onSubmit={handleLogin} className="space-y-4">
                  {/* Mission Status */}
                  <div className="flex items-center justify-between text-xs text-green-400 font-mono mb-4">
                    <span>● SYSTEM ONLINE</span>
                    <span>READY FOR AUTHENTICATION</span>
                  </div>

                  {/* Email Field */}
                  <div className="space-y-2">
                    <Label
                      htmlFor="email"
                      className="text-blue-300 font-mono text-sm"
                    >
                      ASTRONAUT ID
                    </Label>
                    <div className="relative">
                      <User className="absolute left-3 top-3 h-4 w-4 text-blue-400" />
                      <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        className="pl-10 bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                        placeholder="Enter astronaut email..."
                        required
                      />
                      {errors.email && (
                        <p className="text-red-400 text-xs mt-1">{errors.email}</p>
                      )}
                    </div>
                  </div>

                  {/* Password Field */}
                  <div className="space-y-2">
                    <Label
                      htmlFor="password"
                      className="text-blue-300 font-mono text-sm"
                    >
                      SECURITY CODE
                    </Label>
                    <div className="relative">
                      <Lock className="absolute left-3 top-3 h-4 w-4 text-blue-400" />
                      <Input
                        id="password"
                        type={showPassword ? "text" : "password"}
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        className="pl-10 pr-10 bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                        placeholder="Enter security code..."
                        required
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-3 text-blue-400 hover:text-blue-300"
                      >
                        {showPassword ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </button>
                      {errors.password && (
                        <p className="text-red-400 text-xs mt-1">{errors.password}</p>
                      )}
                    </div>
                  </div>

                  {/* Login Button */}
                  <div className="pt-4">
                    <Button
                      type="submit"
                      disabled={processing}
                      className="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-mono tracking-wider py-3 text-sm border border-blue-400/50 shadow-lg shadow-blue-500/25 disabled:opacity-50 transition-transform hover:scale-102 active:scale-98"
                    >
                      {processing ? (
                        <div className="flex items-center space-x-2">
                          <div className="animate-spin">
                            <Star className="h-4 w-4" />
                          </div>
                          <span>AUTHENTICATING...</span>
                        </div>
                      ) : (
                        <div className="flex items-center space-x-2">
                          <Rocket className="h-4 w-4" />
                          <span>INITIATE LAUNCH SEQUENCE</span>
                        </div>
                      )}
                    </Button>
                  </div>
                </form>

                {/* Status Bar */}
                <div className="mt-6 pt-4 border-t border-blue-500/30">
                  <div className="flex justify-between text-xs text-gray-400 font-mono">
                    <span>HOUSTON, TX</span>
                    <span>{new Date().toLocaleTimeString()}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Side - Mission Stats */}
        <div className="w-80 bg-gradient-to-b from-black/80 to-black/60 backdrop-blur-sm border-l border-blue-500/30 p-6 flex flex-col justify-center animate-slide-in-right delay-500">
          <div className="space-y-6">
            <div className="text-center">
              <h2 className="text-lg font-mono text-blue-400 mb-4">MISSION STATUS</h2>
            </div>

            {/* Mission Stats */}
            <div className="space-y-4">
              {[
                { label: "ORBITAL VELOCITY", value: "17,500 MPH", status: "NOMINAL" },
                { label: "ALTITUDE", value: "240 MILES", status: "STABLE" },
                { label: "FUEL REMAINING", value: "78.2%", status: "GOOD" },
                { label: "COMMUNICATION", value: "STRONG", status: "ACTIVE" },
              ].map((stat, index) => (
                <div
                  key={stat.label}
                  className="bg-black/40 border border-blue-500/20 rounded p-3 animate-slide-in-right"
                  style={{ animationDelay: `${1000 + index * 200}ms` }}
                >
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-blue-300 font-mono">{stat.label}</span>
                    <span className="text-green-400 text-xs font-mono">● {stat.status}</span>
                  </div>
                  <div className="text-white font-mono text-sm mt-1">{stat.value}</div>
                </div>
              ))}
            </div>

            {/* NASA Logo Area */}
            <div className="text-center pt-6 border-t border-blue-500/30 animate-fade-in delay-2000">
              <div className="flex justify-center items-center space-x-2 mb-2">
                <Earth className="h-6 w-6 text-blue-400" />
                <Moon className="h-4 w-4 text-gray-400" />
              </div>
              <div className="text-xs text-gray-400 font-mono">
                NATIONAL AERONAUTICS AND<br />
                SPACE ADMINISTRATION
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Status Bar */}
      <div className="absolute bottom-0 left-0 right-0 bg-black/80 backdrop-blur-sm border-t border-blue-500/30 p-2 animate-slide-in-up delay-1500">
        <div className="flex justify-between items-center text-xs text-gray-400 font-mono">
          <span>MISSION CONTROL CENTER - HOUSTON</span>
          <span>EST. 1961 | APOLLO PROGRAM</span>
          <span>SECURE CONNECTION ESTABLISHED</span>
        </div>
      </div>
    </div>
  );
}

// No layout for login page as it has its own full-screen design
Login.layout = page => page;

export default Login;