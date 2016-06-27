import org.apache.jackrabbit.webdav.DavServletResponse;

class CheckMapGetNotNull {
  int pattern(Map codeMap, Class exceptionClass) {
    Integer code = (Integer) codeMap.get(exceptionClass);
    if (code == null) {
      for (Iterator it = codeMap.keySet().iterator(); it.hasNext();) {
        Class jcrExceptionClass = (Class) it.next();
        if (jcrExceptionClass.isAssignableFrom(exceptionClass)) {
          code = (Integer) codeMap.get(jcrExceptionClass);
          break;
        }
      }
      if (code == null) {
        code = new Integer(DavServletResponse.SC_FORBIDDEN); // fallback
      }
    }
    return code.intValue();
  }
}