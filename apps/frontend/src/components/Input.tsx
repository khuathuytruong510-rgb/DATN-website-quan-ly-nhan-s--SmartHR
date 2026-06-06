import './Input.css';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
}

export default function Input({ label, ...props }: InputProps) {
  return (
    <label className="input-group">
      {label && <span className="input-label">{label}</span>}
      <input className="input-field" {...props} />
    </label>
  );
}
